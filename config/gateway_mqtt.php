<?php
// gateway_mqtt.php — debug-heavy version with retry queue
// Letakkan di folder config/, gunakan path absolut untuk nohup

require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/database.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

date_default_timezone_set('Asia/Jakarta'); // agar timestamp konsisten

$logFile = __DIR__ . '/mqtt_debug.log';
$failedQueueFile = __DIR__ . '/failed_queue.jsonl'; // setiap line = raw JSON pesan yang gagal

function append_log($file, $text) {
    file_put_contents($file, "[".date('Y-m-d H:i:s')."] " . $text . PHP_EOL, FILE_APPEND);
}

// MQTT config
$server = 'broker.emqx.io';
$port = 1883;
$clientId = 'php_gateway_main'; // tetap (jangan uniqid)
$username = 'emqx_test';
$password = 'emqx_test';

$connectionSettings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setKeepAliveInterval(60)
    ->setUseTls(false);

// init mqtt client
$mqtt = new MqttClient($server, $port, $clientId);

// sensor list
$sensorList = [
    'tegangan',
    'arus',
    'waterlvA',
    'waterlvB',
    'flowrate',
    'totalwater',
    'rssi'
];

// helper: tulis pesan gagal ke queue (json lines)
function push_failed_queue($file, $topic, $message) {
    $entry = [
        'ts' => date('c'),
        'topic' => $topic,
        'message' => $message
    ];
    file_put_contents($file, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// konek mqtt with retry
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

// ensure DB connection utility (lebih detail logging)
function ensure_db_connection(&$connection, $logFile) {
    // check existence
    if (!isset($connection) || !($connection instanceof mysqli)) {
        append_log($logFile, "DEBUG: \$connection tidak ada atau tidak instance mysqli. Include ulang database.php");
        include __DIR__ . '/database.php';
    }

    // ping
    if (!@mysqli_ping($connection)) {
        append_log($logFile, "DEBUG: mysqli_ping gagal, attempt reconnect...");
        // tutup dulu jika ada
        if (isset($connection) && ($connection instanceof mysqli)) {
            @mysqli_close($connection);
        }
        include __DIR__ . '/database.php';
        if (!@mysqli_ping($connection)) {
            append_log($logFile, "❌ DB reconnect gagal: " . (isset($connection) ? mysqli_error($connection) : 'no connection object'));
            return false;
        } else {
            append_log($logFile, "✅ DB reconnect berhasil.");
        }
    } else {
        append_log($logFile, "DEBUG: mysqli_ping OK.");
    }
    return true;
}

// callback
$callback = function($topic, $message) use (&$connection, $logFile, $sensorList, $failedQueueFile) {
    try {
        append_log($logFile, "📩 Received on $topic: $message");
        echo "Pesan diterima di $topic: $message\n";

        // decode
        $data = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $err = "⚠️ JSON decode error: " . json_last_error_msg();
            append_log($logFile, $err);
            echo $err . "\n";
            push_failed_queue($failedQueueFile, $topic, $message);
            return;
        }

        if (!isset($data['Node'])) {
            $err = "⚠️ Field Node tidak ada. Discard.";
            append_log($logFile, $err);
            echo $err . "\n";
            push_failed_queue($failedQueueFile, $topic, $message);
            return;
        }

        // cek DB connection
        if (!ensure_db_connection($connection, $logFile)) {
            append_log($logFile, "❌ ensure_db_connection() false — push ke failed queue dan return.");
            push_failed_queue($failedQueueFile, $topic, $message);
            return;
        }

        $node = intval($data['Node']);
        $rssi = isset($data['rssi']) && is_numeric($data['rssi']) ? floatval($data['rssi']) : 0.0;

        append_log($logFile, "DEBUG: Mulai prepare statement (node={$node})");
        $stmt = mysqli_prepare($connection,
            "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at)
             VALUES (?, 'sensor', ?, ?, ?, ?, NOW())"
        );

        if ($stmt === false) {
            $err = "❌ Prepare gagal: errno=" . mysqli_errno($connection) . " error=" . mysqli_error($connection);
            append_log($logFile, $err);
            echo $err . "\n";
            push_failed_queue($failedQueueFile, $topic, $message);
            return;
        } else {
            append_log($logFile, "DEBUG: Prepare OK.");
        }

        foreach ($sensorList as $sensor) {
            if (!array_key_exists($sensor, $data)) {
                $warn = "⚠️ Field '$sensor' tidak ada pada Node {$node}.";
                append_log($logFile, $warn);
                echo $warn . "\n";
                continue;
            }

            $value = floatval($data[$sensor]);
            mysqli_stmt_bind_param($stmt, "isdds", $node, $sensor, $value, $rssi, $topic);

            $exec = @mysqli_stmt_execute($stmt);
            $stmt_errno = mysqli_stmt_errno($stmt);
            $stmt_err = mysqli_stmt_error($stmt);

            if ($exec === false) {
                $err = "❌ Execute gagal (node={$node}, sensor={$sensor}): stmt_errno={$stmt_errno} stmt_err={$stmt_err} mysqli_err=" . mysqli_error($connection);
                append_log($logFile, $err);
                echo $err . "\n";

                // retry logic: reconnect & try once
                append_log($logFile, "DEBUG: mencoba reconnect DB & retry insert untuk sensor {$sensor}");
                include __DIR__ . '/database.php';
                if (@mysqli_ping($connection)) {
                    $stmtRetry = mysqli_prepare($connection,
                        "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at)
                         VALUES (?, 'sensor', ?, ?, ?, ?, NOW())"
                    );
                    if ($stmtRetry) {
                        mysqli_stmt_bind_param($stmtRetry, "isdds", $node, $sensor, $value, $rssi, $topic);
                        $execRetry = @mysqli_stmt_execute($stmtRetry);
                        if ($execRetry !== false) {
                            append_log($logFile, "✅ Retry insert sukses (node={$node}, sensor={$sensor})");
                            echo "✅ Retry insert sukses (node={$node}, sensor={$sensor})\n";
                        } else {
                            $err2 = "❌ Retry insert gagal: " . mysqli_stmt_error($stmtRetry) . " errno=" . mysqli_stmt_errno($stmtRetry);
                            append_log($logFile, $err2);
                            echo $err2 . "\n";
                            // push entire message to failed queue (so we don't lose whole payload)
                            push_failed_queue($failedQueueFile, $topic, $message);
                        }
                        mysqli_stmt_close($stmtRetry);
                    } else {
                        append_log($logFile, "❌ Retry prepare failed: " . mysqli_error($connection));
                        push_failed_queue($failedQueueFile, $topic, $message);
                    }
                } else {
                    append_log($logFile, "❌ Retry DB ping gagal: " . (isset($connection) ? mysqli_error($connection) : 'no connection'));
                    push_failed_queue($failedQueueFile, $topic, $message);
                }
            } else {
                append_log($logFile, "✅ Insert sukses (node={$node}, sensor={$sensor}, value={$value})");
                echo "✅ Insert sukses (node={$node}, sensor={$sensor}, value={$value})\n";
            }
        }

        mysqli_stmt_close($stmt);
        append_log($logFile, "✅ Data Node {$node} selesai diproses.");

    } catch (Throwable $t) {
        // Tangkap fatal error & simpan pesan supaya tidak hilang
        append_log($logFile, "EXCEPTION in callback: " . $t->getMessage());
        echo "EXCEPTION: " . $t->getMessage() . "\n";
        push_failed_queue($failedQueueFile, $topic, $message);
    }
};

// konek & subscribe
connect_mqtt($mqtt, $connectionSettings, $logFile);
$mqtt->subscribe('SmIr/data', $callback, 0);

// main loop with auto reconnect and re-subscribe
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
