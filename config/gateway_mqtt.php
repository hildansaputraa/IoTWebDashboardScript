<?php
// gateway_mqtt.php — robust reconnect on "MySQL server has gone away"
// Put this file in config/ and run with nohup as before.

require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/database.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

date_default_timezone_set('Asia/Jakarta');

$logFile = __DIR__ . '/mqtt_debug.log';
$failedQueueFile = __DIR__ . '/failed_queue.jsonl';

function append_log($file, $text) {
    file_put_contents($file, "[".date('Y-m-d H:i:s')."] " . $text . PHP_EOL, FILE_APPEND);
}

function push_failed_queue($file, $topic, $message) {
    $entry = [
        'ts' => date('c'),
        'topic' => $topic,
        'message' => $message
    ];
    file_put_contents($file, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Helper: reconnect database (close + include database.php), return true on success
function reconnect_db(&$connection, $logFile) {
    append_log($logFile, "DEBUG: reconnect_db() mulai — menutup koneksi lama jika ada.");
    if (isset($connection) && ($connection instanceof mysqli)) {
        @mysqli_close($connection);
    }
    // re-include database.php (ini harus mendefinisikan $connection)
    include __DIR__ . '/database.php';
    // kecilkan jeda sejenak
    usleep(200000); // 200ms
    if (isset($connection) && ($connection instanceof mysqli) && @mysqli_ping($connection)) {
        append_log($logFile, "✅ reconnect_db(): koneksi baru berhasil.");
        return true;
    } else {
        append_log($logFile, "❌ reconnect_db(): gagal membuat koneksi baru.");
        return false;
    }
}

$server = 'broker.emqx.io';
$port = 1883;
$clientId = 'php_gateway_main';
$username = 'emqx_test';
$password = 'emqx_test';

$connectionSettings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setKeepAliveInterval(60)
    ->setUseTls(false);

$mqtt = new MqttClient($server, $port, $clientId);

$sensorList = [
    'tegangan',
    'arus',
    'waterlvA',
    'waterlvB',
    'flowrate',
    'totalwater',
    'rssi'
];

// connect mqtt with retry
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

// ensure DB connection (try to ping, else reconnect)
function ensure_db_connection(&$connection, $logFile) {
    if (!isset($connection) || !($connection instanceof mysqli)) {
        append_log($logFile, "DEBUG: connection object invalid, include database.php");
        include __DIR__ . '/database.php';
    }
    if (!@mysqli_ping($connection)) {
        append_log($logFile, "DEBUG: mysqli_ping gagal, mencoba reconnect_db()");
        return reconnect_db($connection, $logFile);
    }
    return true;
}

// The callback with explicit "MySQL server has gone away" handling
$callback = function($topic, $message) use (&$connection, $logFile, $sensorList, $failedQueueFile) {
    append_log($logFile, "📩 Received on $topic: $message");
    echo "Pesan diterima di $topic: $message\n";

    $data = json_decode($message, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $err = "⚠️ JSON decode error: " . json_last_error_msg();
        append_log($logFile, $err);
        echo $err . "\n";
        push_failed_queue($failedQueueFile, $topic, $message);
        return;
    }
    if (!isset($data['Node'])) {
        $err = "⚠️ Field Node tidak ditemukan.";
        append_log($logFile, $err);
        echo $err . "\n";
        push_failed_queue($failedQueueFile, $topic, $message);
        return;
    }

    // ensure DB ready
    if (!ensure_db_connection($connection, $logFile)) {
        append_log($logFile, "❌ ensure_db_connection() gagal — push ke failed queue.");
        push_failed_queue($failedQueueFile, $topic, $message);
        return;
    }

    $node = intval($data['Node']);
    $rssi = isset($data['rssi']) && is_numeric($data['rssi']) ? floatval($data['rssi']) : 0.0;

    // Prepare once
    $stmt = mysqli_prepare($connection,
        "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at)
         VALUES (?, 'sensor', ?, ?, ?, ?, NOW())"
    );
    if ($stmt === false) {
        $err = "❌ Prepare gagal awal: " . mysqli_error($connection);
        append_log($logFile, $err);
        echo $err . "\n";
        push_failed_queue($failedQueueFile, $topic, $message);
        return;
    }

    foreach ($sensorList as $sensor) {
        if (!array_key_exists($sensor, $data)) {
            append_log($logFile, "⚠️ Field '$sensor' tidak ada pada Node {$node}, skip");
            continue;
        }

        $value = floatval($data[$sensor]);

        // Try execute with up to 2 attempts if "MySQL server has gone away"
        $attempt = 0;
        $maxAttempts = 2;
        $success = false;

        while ($attempt < $maxAttempts && !$success) {
            $attempt++;
            mysqli_stmt_bind_param($stmt, "isdds", $node, $sensor, $value, $rssi, $topic);

            $exec = @mysqli_stmt_execute($stmt);

            if ($exec !== false) {
                append_log($logFile, "✅ Insert sukses (node={$node}, sensor={$sensor}, value={$value})");
                echo "✅ Insert sukses (node={$node}, sensor={$sensor}, value={$value})\n";
                $success = true;
                break;
            } else {
                $stmt_errno = mysqli_stmt_errno($stmt);
                $stmt_err = mysqli_stmt_error($stmt);
                $conn_err = mysqli_error($connection);
                $fullErr = "Attempt {$attempt}: Execute gagal: stmt_errno={$stmt_errno} stmt_err={$stmt_err} conn_err={$conn_err}";
                append_log($logFile, $fullErr);
                echo $fullErr . "\n";

                // If error indicates "server has gone away", try reconnect + re-prepare
                $lower = strtolower($stmt_err . ' ' . $conn_err);
                if (strpos($lower, 'server has gone away') !== false || strpos($lower, 'mysql server has gone away') !== false || strpos($lower, 'gone away') !== false) {
                    append_log($logFile, "DEBUG: Detected 'gone away' — mencoba reconnect_db()");
                    // reconnect DB
                    if (reconnect_db($connection, $logFile)) {
                        // close old stmt and re-prepare on new connection
                        @mysqli_stmt_close($stmt);
                        $stmt = mysqli_prepare($connection,
                            "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at)
                             VALUES (?, 'sensor', ?, ?, ?, ?, NOW())"
                        );
                        if ($stmt === false) {
                            append_log($logFile, "❌ Re-prepare gagal setelah reconnect: " . mysqli_error($connection));
                            echo "❌ Re-prepare gagal: " . mysqli_error($connection) . "\n";
                            // no point retrying more
                            break;
                        } else {
                            append_log($logFile, "DEBUG: Re-prepare sukses, akan retry execute.");
                            // loop will retry
                        }
                    } else {
                        append_log($logFile, "❌ reconnect_db() gagal saat menangani 'gone away'.");
                        // cannot recover here
                        break;
                    }
                } else {
                    // non-recoverable DB error or other error: break and push to failed queue
                    append_log($logFile, "❌ Execute error non-recoverable atau bukan 'gone away'.");
                    break;
                }
            }
        } // end attempts

        if (!$success) {
            $errMsg = "❌ Setelah {$attempt} percobaan, insert masih gagal untuk node={$node}, sensor={$sensor}. Mem-push pesan full ke failed_queue.";
            append_log($logFile, $errMsg);
            echo $errMsg . "\n";
            push_failed_queue($failedQueueFile, $topic, $message);
            // optional: continue ke sensor berikutnya or break; we'll continue to next sensor
        }
    } // end foreach sensor

    @mysqli_stmt_close($stmt);
    append_log($logFile, "✅ Data Node {$node} selesai diproses (dikirim atau di-queue bila gagal).");
};

connect_mqtt($mqtt, $connectionSettings, $logFile);
$mqtt->subscribe('SmIr/data', $callback, 0);

while (true) {
    try {
        $mqtt->loop(true);
    } catch (Exception $e) {
        append_log($logFile, "⚠️ MQTT disconnected: " . $e->getMessage());
        echo "⚠️ MQTT disconnected: " . $e->getMessage() . "\n";
        try { $mqtt->disconnect(); } catch (Exception $ex) { append_log($logFile, "disconnect error: " . $ex->getMessage()); }
        sleep(5);
        connect_mqtt($mqtt, $connectionSettings, $logFile);
        $mqtt->subscribe('SmIr/data', $callback, 0);
    }
}
?>
