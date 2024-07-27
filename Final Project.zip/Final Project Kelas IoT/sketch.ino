#include <WiFi.h>
#include <MQTT.h>
#include <NusabotSimpleTimer.h>
#include <DHTesp.h>
#include <ESP32Servo.h>
//wifiwokwi
const char ssid[] = "Wokwi-GUEST";
const char pass[] = "";

//pinGPIO
const int ledRed = 16;
const int ledGreen = 17;
const int ledBlue = 4;
const int lampu = 2;
const int pinpotensio = 33;
const int dht = 27;
const int pinServo = 34;

// data
int potensiometer, oldpot = 0;
String oldsuhu = "";
String oldkelembapan = "";
String serial_number = "12345678";

//library
WiFiClient net;
MQTTClient client;
NusabotSimpleTimer timer;
DHTesp dhtSensor;
Servo servo;


void connect() {
  led(1, 0, 0);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
  }
  led(0, 1, 0);
  client.setWill("kelasiottt/status/12345678", "offline", true, 1);
  while (!client.connect("hildan-id", "kelasiottt","caecbzs6erwT0HRk" )) {
    delay(500);
  }
  led(0, 0, 1);
  client.publish("kelasiottt/status/12345678", "online", true, 1);
  //subscribe
  client.subscribe("kelasiottt/"+serial_number+"/lampu",1);
}

void subscribe(String &topic, String &payload){
  if (topic == "kelasiottt/"+serial_number+"/lampu"){
    if (payload == "nyala"){
      digitalWrite(lampu, 1);
    }
    else if (payload == "mati"){
      digitalWrite(lampu, 0);
    }
  }

  if (topic == "kelasiottt/"+serial_number+"/servo"){
    int posServo = payload.toInt();
    servo.write(posServo);
  }
  Serial.print(topic + payload);
}

void led(bool red, bool green, bool blue) {
  digitalWrite(ledRed, red);
  digitalWrite(ledGreen, green);
  digitalWrite(ledBlue, blue);
}

void pubPotensio(){
  potensiometer = analogRead(pinpotensio);
  if (potensiometer != oldpot){
    Serial.println(potensiometer);
    client.publish("kelasiottt/"+serial_number+"/potensio", String(potensiometer), false, 1);
    oldpot = potensiometer;
  }
}

void pubDht(){
 
  TempAndHumidity data = dhtSensor.getTempAndHumidity();
  if (String(data.temperature, 2) != oldsuhu){
  client.publish("pehaniot/"+serial_number+"/temperature",String(data.temperature, 2), true, 1);
  oldsuhu = String(data.temperature, 2);
  }
  if(String(data.humidity, 1) != oldkelembapan){
  client.publish("kelasiottt/"+serial_number+"/humidity", String(data.humidity, 1), true, 1);
  oldkelembapan = String(data.humidity, 1);
  }

}




void setup() {
  pinMode(ledRed, OUTPUT);
  pinMode(ledGreen, OUTPUT);
  pinMode(ledBlue, OUTPUT);
  pinMode(lampu, OUTPUT);
  pinMode(pinpotensio, INPUT);
  dhtSensor.setup(dht, DHTesp::DHT22);
  servo.attach(pinServo, 500, 2400);
  Serial.begin(9600);
  
  WiFi.begin(ssid, pass);
  client.begin("kelasiottt.cloud.shiftr.io", net);
  connect();
  client.onMessage(subscribe);
  timer.setInterval(500, pubPotensio);
  timer.setInterval(500, pubDht);
}

void loop() {
  client.loop();
  if (!client.connected()) {
    connect();
  }
  timer.run();
  //client.publish("hildan/relay","data",0);
  delay(10);
}