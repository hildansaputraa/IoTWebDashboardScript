<?php
// ============================================================================
// gateway_mqtt.php (versi revisi final & stabil)
// Fungsi:
//  - Subscribe topic SmIr/data dari broker MQTT
//  - Simpan payload JSON ke MySQL
//  - Otomatis reconnect MQTT & MySQL bila koneksi terputus
//  - Logging aktivitas & error ke file log
// ============================================================================

// -------------------------------------
// Load composer & koneksi database
// -------------------------------------
require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/database.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// -------------------------------------
// Pengaturan error dan log
// -------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logFile = __DIR__ . '/mqtt_debug.log';

// helper log sederhana
function append_log($file, $text) {
    file_put_contents($file, "[" . date('Y-m-d H:i:s') . "] " . $text . PHP_EOL, FILE_APPEND);
}

// -------------------------------------
// Konfigurasi broker MQTT
// -------------------------------------
$server = 'broker.emqx.io';
$port = 1883;
$clientId = 'php_gateway_' . uniqid();
$username = 'emqx_test';
$password = 'emqx_test';

// pengaturan koneksi MQTT
$connectionSettings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setKeepAliveInterval(60)
    ->setUseTls(false);

// -------------------------------------
// Membuat client MQTT
// -------------------------------------
$mqtt = new MqttClient($server, $port, $clientId);

// fungsi koneksi ke broker MQTT dengan retry
function connect_mqtt($mqtt, $settings, $logFile) {
    while (true) {
        try {
            $mqtt->connect($settings, true);
            append_log($logFile, "✅ Terhubung ke broker MQTT");
            echo "✔ Terhubung ke broker MQTT\n";
            return true;
        } catch (Exception $e) {
            append_log($logFile, "❌ Gagal konek ke broker: " . $e->getMessage());
            echo "❌ Gagal konek ke broker: " . $e->getMessage() . "\n";
            sleep(5);
        }
    }
}

// konek pertama kali
connect_mqtt($mqtt, $connectionSettings, $logFile);

// -------------------------------------
// Daftar field sensor yang diharapkan
// -------------------------------------
$sensorList = [
    'tegangan',
    'arus',
    'waterlvA',
    'waterlvB',
    'flowrate',
    'totalwater',
    'rssi'
];

// -------------------------------------
// Callback utama saat data diterima
// -------------------------------------
$mqtt->subscribe('SmIr/data', function ($topic, $message) use (&$connection, $logFile, $sensorList) {

    append_log($logFile, "📩 Received on $topic: $message");
    echo "Pesan diterima di $topic: $message\n";

    // Pastikan data JSON valid
    $data = json_decode($message, true);
    if (!is_array($data) || !isset($data['Node'])) {
        $err = "⚠️ Data tidak valid atau field 'Node' hilang. Diabaikan.";
        append_log($logFile, $err);
        echo $err . "\n";
        return;
    }

    // Fungsi internal untuk memastikan koneksi DB aktif
    $ensureDb = function() use (&$connection, $logFile) {
        if (!isset($connection) || !($connection instanceof mysqli)) {
            append_log($logFile, "DB object tidak valid, mencoba include ulang database.php");
            include __DIR__ . '/database.php';
        }

        if (!@mysqli_ping($connection)) {
            append_log($logFile, "DB ping gagal, mencoba reconnect...");
            include __DIR__ . '/database.php';
            if (!@mysqli_ping($connection)) {
                append_log($logFile, "❌ DB reconnect gagal.");
                return false;
            }
            append_log($logFile, "✅ DB reconnect berhasil.");
        }
        return true;
    };

    if (!$ensureDb()) {
        append_log($logFile, "❌ Koneksi database gagal. Data diabaikan.");
        return;
    }

    $node = intval($data['Node']);
    $rssi = isset($data['rssi']) && is_numeric($data['rssi']) ? floatval($data['rssi']) : 0.0;

    // Siapkan statement insert
    $stmt = mysqli_prepare($connection,
        "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at)
         VALUES (?, 'sensor', ?, ?, ?, ?, NOW())"
    );

    if ($stmt === false) {
        $err = "❌ Prepare gagal: " . mysqli_error($connection);
        append_log($logFile, $err);
        echo $err . "\n";
        return;
    }

    // Loop setiap sensor dalam daftar
    foreach ($sensorList as $sensor) {
        if (!array_key_exists($sensor, $data)) {
            $warn = "⚠️ Field '$sensor' tidak ada pada Node {$data['Node']}.";
            append_log($logFile, $warn);
            echo $warn . "\n";
            continue;
        }

        $value = floatval($data[$sensor]);
        mysqli_stmt_bind_param($stmt, "isdds", $node, $sensor, $value, $rssi, $topic);
        $exec = mysqli_stmt_execute($stmt);

        if ($exec === false) {
            $err = "❌ Insert gagal (node={$node}, sensor={$sensor}): " . mysqli_stmt_error($stmt);
            append_log($logFile, $err);
            echo $err . "\n";

            // coba reconnect DB dan retry 1x
            include __DIR__ . '/database.php';
            if (@mysqli_ping($connection)) {
                $stmtRetry = mysqli_prepare($connection,
                    "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at)
                     VALUES (?, 'sensor', ?, ?, ?, ?, NOW())"
                );
                if ($stmtRetry) {
                    mysqli_stmt_bind_param($stmtRetry, "isdds", $node, $sensor, $value, $rssi, $topic);
                    if (mysqli_stmt_execute($stmtRetry)) {
                        append_log($logFile, "✅ Retry insert sukses (node={$node}, sensor={$sensor})");
                        echo "✅ Retry insert sukses (node={$node}, sensor={$sensor})\n";
                    } else {
                        append_log($logFile, "❌ Retry insert gagal: " . mysqli_stmt_error($stmtRetry));
                    }
                    mysqli_stmt_close($stmtRetry);
                }
            }
        } else {
            append_log($logFile, "✅ Insert sukses (node={$node}, sensor={$sensor}, value={$value})");
            echo "✅ Insert sukses (node={$node}, sensor={$sensor}, value={$value})\n";
        }
    }

    mysqli_stmt_close($stmt);
    append_log($logFile, "✅ Data Node {$data['Node']} diproses sepenuhnya.");

}, 0);

// -------------------------------------
// Loop utama — auto reconnect MQTT bila terputus
// -------------------------------------
while (true) {
    try {
        $mqtt->loop(true);
    } catch (Exception $e) {
        append_log($logFile, "⚠️ MQTT disconnected: " . $e->getMessage());
        echo "⚠️ MQTT disconnected: " . $e->getMessage() . "\n";
        sleep(5);
        connect_mqtt($mqtt, $connectionSettings, $logFile);
    }
}
?>
