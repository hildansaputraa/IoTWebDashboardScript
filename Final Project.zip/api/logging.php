<?php 

include "../config/database.php";


$webhookResponse = json_decode(file_get_contents('php://input'),true);
$topic = $webhookResponse["topic"];
$payload = $webhookResponse["payload"];

$topicExplode = explode("/", $topic);
$serialNumber =  $topicExplode[1];
$name = $topicExplode[2];

if($topicExplode[2] == "temperature" || $topicExplode[2] == "potentiometer" || $topicExplode[2] == "humidity"){
    $type = "sensor";
} else {
    $type = "actuator";
}

$sql = "INSERT INTO data (serial_number, sensor_actuator, value, name, mqtt_topic)
        VALUES ('$serialNumber', '$type', '$payload','$name', '$topic') ";

mysqli_query($connection,$sql);
?>