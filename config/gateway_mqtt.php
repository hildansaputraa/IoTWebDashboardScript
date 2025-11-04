<?php
// gateway_mqtt.php — revisi: lebih robust reconnect / retry untuk "MySQL server has gone away"
// Letakkan di config/ dan jalankan dengan nohup seperti biasa.

require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/database.php'; // harus mendefinisikan $connection

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

date_default_timezone_set('Asia/Jakarta');

$logFile = __DIR__ . '/mqtt_debug.log';
$failedQueueFile = __DIR__ . '/failed_queue.jsonl';

function append_log($file, $text) {
    file_put_contents($file, "[".date('Y-m-d H:i:s')."] " . $text . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function push_failed_queue($file, $topic, $message) {
    $entry = [
        'ts' => date('c'),
        'topic' => $topic,
        'message' => $message
    ];
    file_put_contents($file, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// reconnect_db: attempt to recreate $connection by re-including database.php
function reconnect_db(&$connection, $logFile) {
    append_log($logFile, "DEBUG: reconnect_db() mulai — menutup koneksi lama jika ada.");
    if (isset($connection) && ($connection instanceof mysqli)) {
        @mysqli_close($connection);
    }
    include __DIR__ . '/database.php'; // database.php must set $connection
    usleep(200000); // 200 ms
    if (isset($connection) && ($connection instanceof mysqli) && mysqli_ping($connection)) {
        append_log($logFile, "✅ reconnect_db(): koneksi baru berhasil.");
        return true;
    } else {
        append_log($logFile, "❌ reconnect_db(): gagal membuat koneksi baru (" . (isset($connection) ? mysqli_error($connection) : 'no connection object') . ").");
        return false;
    }
}

$server = 'broker.emqx.io';
$port = 1883;
$clientId = 'php_gateway_main'; // static client id
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

// ensure_db_connection: ping or reconnect
function ensure_db_connection(&$connection, $logFile) {
    if (!isset($connection) || !($connection instanceof mysqli)) {
        append_log($logFile, "DEBUG: connection object invalid, include database.php");
        include __DIR__ . '/database.php';
    }
    // try ping (no suppression so errors visible)
    if (!@mysqli_ping($connection)) {
        append_log($logFile, "DEBUG: mysqli_ping gagal, mencoba reconnect_db()");
        return reconnect_db($connection, $logFile);
    }
    append_log($logFile, "DEBUG: mysqli_ping OK.");
    return true;
}

// Process a decoded message (array $data) with retries for DB gone-away
function process_message(&$connection, $data, $topic, $logFile, $failedQueueFile, $sensorList) {
    // Validate Node
    if (!isset($data['Node'])) {
        append_log($logFile, "⚠️ Field 'Node' tidak ditemukan — pesan dibuang");
        return false;
    }

    $node = intval($data['Node']);
    $rssi = isset($data['rssi']) && is_numeric($data['rssi']) ? floatval($data['rssi']) : 0.0;

    // Ensure DB live before prepare
    if (!ensure_db_connection($connection, $logFile)) {
        append_log($logFile, "❌ ensure_db_connection() gagal saat memproses Node {$node}");
        return false;
    }

    // prepare statement
    $stmt = mysqli_prepare($connection,
        "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at)
         VALUES (?, 'sensor', ?, ?, ?, ?, NOW())"
    );

    if ($stmt === false) {
        append_log($logFile, "❌ Prepare gagal awal: " . mysqli_error($connection));
        return false;
    }
    append_log($logFile, "DEBUG: Prepare OK untuk Node {$node}");

    // For each sensor try execute, with reconnect+reprepare if "gone away"
    foreach ($sensorList as $sensor) {
        if (!array_key_exists($sensor, $data)) {
            append_log($logFile, "⚠️ Field '$sensor' tidak ada pada Node {$node}, skip");
            continue;
        }
        $value = floatval($data[$sensor]);

        $attempt = 0;
        $maxAttempts = 2;
        $success = false;

        while ($attempt < $maxAttempts && !$success) {
            $attempt++;
            mysqli_stmt_bind_param($stmt, "isdds", $node, $sensor, $value, $rssi, $topic);
            $exec = @mysqli_stmt_execute($stmt);
            if ($exec !== false) {
                append_log($logFile, "✅ Insert sukses (node={$node}, sensor={$sensor}, value={$value})");
                $success = true;
                break;
            } else {
                $stmt_errno = mysqli_stmt_errno($stmt);
                $stmt_err = mysqli_stmt_error($stmt);
                $conn_err = mysqli_error($connection);
                append_log($logFile, "Attempt {$attempt}: Execute gagal: stmt_errno={$stmt_errno} stmt_err={$stmt_err} conn_err={$conn_err}");

                $combined = strtolower(trim($stmt_err . ' ' . $conn_err));
                // If server has gone away, try reconnect and re-prepare
                if (strpos($combined, 'gone away') !== false || strpos($combined, 'server has gone away') !== false) {
                    append_log($logFile, "DEBUG: Detected 'gone away' — mencoba reconnect_db()");
                    if (reconnect_db($connection, $logFile)) {
                        @mysqli_stmt_close($stmt);
                        $stmt = mysqli_prepare($connection,
                            "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at)
                             VALUES (?, 'sensor', ?, ?, ?, ?, NOW())"
                        );
                        if ($stmt === false) {
                            append_log($logFile, "❌ Re-prepare gagal setelah reconnect: " . mysqli_error($connection));
                            break;
                        } else {
                            append_log($logFile, "DEBUG: Re-prepare sukses setelah reconnect");
                            // retry loop will continue
                        }
                    } else {
                        append_log($logFile, "❌ reconnect_db() gagal saat menangani 'gone away'");
                        break;
                    }
                } else {
                    // non-recoverable error: break out
                    append_log($logFile, "❌ Execute error non-recoverable atau bukan 'gone away': " . $stmt_err);
                    break;
                }
            }
        } // end attempts

        if (!$success) {
            append_log($logFile, "❌ Setelah {$attempt} percobaan, insert masih gagal untuk node={$node}, sensor={$sensor}");
            @mysqli_stmt_close($stmt);
            return false;
        }
    } // end foreach

    @mysqli_stmt_close($stmt);
    append_log($logFile, "✅ Data Node {$node} selesai diproses.");
    return true;
}

// callback wrapper
$callback = function($topic, $message) use (&$connection, $logFile, $failedQueueFile, $sensorList) {
    append_log($logFile, "📩 Received on $topic: $message");
    echo "Pesan diterima di $topic: $message\n";

    // decode JSON
    $data = json_decode($message, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $err = "⚠️ JSON decode error: " . json_last_error_msg();
        append_log($logFile, $err);
        echo $err . "\n";
        push_failed_queue($failedQueueFile, $topic, $message);
        return;
    }

    // Try to process; on failure push to queue
    try {
        $ok = process_message($connection, $data, $topic, $logFile, $failedQueueFile, $sensorList);
        if (!$ok) {
            append_log($logFile, "❌ process_message returned false — push full message to failed_queue.");
            push_failed_queue($failedQueueFile, $topic, $message);
        }
    } catch (Throwable $t) {
        // catch fatal/unexpected errors
        append_log($logFile, "EXCEPTION in callback: " . $t->getMessage());
        echo "EXCEPTION: " . $t->getMessage() . "\n";
        // attempt reconnect once if it's a "gone away" type
        $msg = strtolower($t->getMessage());
        if (strpos($msg, 'gone away') !== false) {
            append_log($logFile, "DEBUG: Exception indicates 'gone away' — mencoba reconnect_db()");
            if (reconnect_db($connection, $logFile)) {
                // try process once more
                try {
                    $ok2 = process_message($connection, $data, $topic, $logFile, $failedQueueFile, $sensorList);
                    if (!$ok2) push_failed_queue($failedQueueFile, $topic, $message);
                } catch (Throwable $t2) {
                    append_log($logFile, "EXCEPTION saat retry: " . $t2->getMessage());
                    push_failed_queue($failedQueueFile, $topic, $message);
                }
                return;
            }
        }
        // otherwise push to failed queue
        push_failed_queue($failedQueueFile, $topic, $message);
    }
};

// start
connect_mqtt($mqtt, $connectionSettings, $logFile);
$mqtt->subscribe('SmIr/data', $callback, 0);

// main loop with auto reconnect/resubscribe
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
