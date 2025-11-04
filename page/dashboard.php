<?php
include "config/database.php";

// Ambil daftar device aktif
$sql = "SELECT * FROM devices WHERE active='Yes'";
$result = mysqli_query($connection, $sql);

// Ambil fallback data terakhir untuk setiap node dan name
$fallback = [];
$fallbackQuery = mysqli_query($connection, "
  SELECT d1.node, d1.name, d1.value
  FROM data d1
  INNER JOIN (
      SELECT node, name, MAX(created_at) AS last_time
      FROM data
      GROUP BY node, name
  ) d2 ON d1.node = d2.node AND d1.name = d2.name AND d1.created_at = d2.last_time
");
if ($fallbackQuery) {
  while ($row = mysqli_fetch_assoc($fallbackQuery)) {
    $fallback[$row['node']][$row['name']] = $row['value'];
  }
}
?>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">
    <div class="content-wrapper">
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Dashboard</h1>
            </div>
          </div>
        </div>
      </div>

      <!-- Main content -->
      <div class="content">
        <div class="container-fluid">

          <!-- Sensor dasar -->
          <div class="row">
            <div class="col-lg-4">
              <div class="small-box bg-warning">
                <div class="inner">
                  <h3 id="potentiometer"><?php echo $fallback['12345678']['potentiometer'] ?? '-' ?></h3>
                  <p>Potentiometer</p>
                </div>
                <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="small-box bg-danger">
                <div class="inner">
                  <h3 id="temperature"><?php echo $fallback['12345678']['temperature'] ?? '-' ?></h3>
                  <p>Temperature</p>
                </div>
                <div class="icon"><i class="fas fa-temperature-high"></i></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="small-box bg-gray">
                <div class="inner">
                  <h3><span id="humidity"><?php echo $fallback['12345678']['humidity'] ?? '-' ?></span>%</h3>
                  <p>Humidity</p>
                </div>
                <div class="icon"><i class="fas fa-water"></i></div>
              </div>
            </div>
          </div>

          <!-- Lamp Button -->
          <div class="row">
            <div class="col-lg-6">
              <div class="card card-lightblue">
                <div class="card-header"><h3 class="card-title">Lampu 1</h3></div>
                <div class="card-body table-responsive pad">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-danger" id="label-lampu1-nyala">
                      <input type="radio" name="lampu1" onchange="publishLamp(this)" id="lampu1nyala" autocomplete="off"> On
                    </label>
                    <label class="btn btn-danger" id="label-lampu1-mati">
                      <input type="radio" name="lampu1" onchange="publishLamp(this)" id="lampu1mati" autocomplete="off"> Off
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <div class="card card-lightblue">
                <div class="card-header"><h3 class="card-title">Lampu 2</h3></div>
                <div class="card-body table-responsive pad">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-danger" id="label-lampu2-nyala">
                      <input type="radio" name="lampu2" onchange="publishLamp(this)" id="lampu2nyala" autocomplete="off"> On
                    </label>
                    <label class="btn btn-danger" id="label-lampu2-mati">
                      <input type="radio" name="lampu2" onchange="publishLamp(this)" id="lampu2mati" autocomplete="off"> Off
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Monitoring Sensor -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card card-success">
                <div class="card-header">
                  <h3 class="card-title">Monitoring Sensor</h3>
                </div>
                <div class="card-body">
                  <div class="row">
                    <?php for ($i = 1; $i <= 4; $i++) { 
                      $nodeData = $fallback[$i] ?? [];
                      // Ambil waktu terakhir untuk node ini
                      $queryTime = mysqli_query($connection, "SELECT MAX(created_at) AS last_time FROM data WHERE node = $i");
                      $timeRow = mysqli_fetch_assoc($queryTime);
                      $lastTime = $timeRow ? date('H:i:s', strtotime($timeRow['last_time'])) : '-';
                    ?>
                      <div class="col-md-3">
                        <div class="card card-<?php echo ['primary', 'success', 'warning', 'danger'][$i - 1]; ?>">
                          <div class="card-header">
                            <h3 class="card-title">Node <?php echo $i; ?></h3>
                          </div>
                          <div class="card-body">
                            <p>Tegangan: <span id="node<?php echo $i; ?>-tegangan"><?php echo $nodeData['tegangan'] ?? '-' ?></span> V</p>
                            <p>Arus: <span id="node<?php echo $i; ?>-arus"><?php echo $nodeData['arus'] ?? '-' ?></span> mA</p>
                            <p>Water Level A: <span id="node<?php echo $i; ?>-waterlvA"><?php echo $nodeData['waterlvA'] ?? '-' ?></span> cm</p>
                            <p>Water Level B: <span id="node<?php echo $i; ?>-waterlvB"><?php echo $nodeData['waterlvB'] ?? '-' ?></span> cm</p>
                            <p>Flow Rate: <span id="node<?php echo $i; ?>-flowrate"><?php echo $nodeData['flowrate'] ?? '-' ?></span> L/min</p>
                            <p>Total Water: <span id="node<?php echo $i; ?>-totalwater"><?php echo $nodeData['totalwater'] ?? '-' ?></span> L</p>
                            <p>RSSI: <span id="node<?php echo $i; ?>-rssi"><?php echo $nodeData['rssi'] ?? '-' ?></span> dBm</p>
                            <p class="text-muted" style="font-size: 0.85em; font-style: italic; margin-top: -5px;">
                              Data ini terakhir pada: <span id="node<?php echo $i; ?>-time"><?php echo $lastTime; ?></span>
                            </p>
                          </div>
                        </div>
                      </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          </div>


          <!-- Gambar Dashboard -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card card-default">
                <div class="card-header bg-primary text-white"><h3 class="card-title">Monitoring Dashboard</h3></div>
                <div class="card-body text-center">
                  <img src="dist/img/maps.jpg" alt="Network Layout" class="img-fluid rounded" style="max-height: 300px;">
                </div>
              </div>
            </div>
          </div>

          <!-- Devices Status -->
          <div class="row">
            <div class="col-12">
              <div class="card card-lightblue">
                <div class="card-header"><h3 class="card-title">Devices Status</h3></div>
                <div class="card-body table-responsive p-0" style="height: 300px;">
                  <table class="table table-head-fixed text-nowrap">
                    <thead>
                      <tr><th>Serial Number</th><th>Location</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                      <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                          <td><?php echo $row['serial_number'] ?></td>
                          <td><?php echo $row['location'] ?></td>
                          <td style="color:red" id="SmIr/status/<?php echo $row['serial_number'] ?>">offline</td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- MQTT Script -->
  <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>

  <script>
    const clientId = Math.random().toString(16).substr(2, 8);
    const host = 'wss://broker.emqx.io:8084/mqtt';

    const options = {
      keepalive: 30,
      clientId,
      username: "emqx_test",
      password: "emqx_test",
      protocolId: 'MQTT',
      protocolVersion: 4,
      clean: true,
      reconnectPeriod: 1000,
      connectTimeout: 30 * 1000,
    };

    console.log("Menghubungkan ke broker...");
    const client = mqtt.connect(host, options);

    client.on("connect", () => {
      console.log("Berhasil connect ke broker!");
      const statusEl = document.getElementById("status");
      if (statusEl) {
        statusEl.innerHTML = "Terhubung";
        statusEl.style.color = "green";
      }

      client.subscribe("kelasiottt/#", { qos: 1 });
      client.subscribe("SmIr/data", { qos: 1 });
      client.subscribe("SmIr/status/#", { qos: 1 });
    });

    client.on("message", function(topic, payload) {
      payload = payload.toString();

      if (topic === "kelasiottt/12345678/temperature")
        document.getElementById("temperature").innerHTML = payload;
      else if (topic === "kelasiottt/12345678/humidity")
        document.getElementById("humidity").innerHTML = payload;
      else if (topic === "kelasiottt/12345678/potentiometer")
        document.getElementById("potentiometer").innerHTML = payload;
      else if (topic === "kelasiottt/12345678/lampu") {
        if (payload === "nyala") {
          document.getElementById("label-lampu1-nyala").classList.add("active");
          document.getElementById("label-lampu1-mati").classList.remove("active");
        } else {
          document.getElementById("label-lampu1-nyala").classList.remove("active");
          document.getElementById("label-lampu1-mati").classList.add("active");
        }
      }

      // Update status perangkat
      if (topic.startsWith("SmIr/status/")) {
        const el = document.getElementById(topic);
        if (el) {
          el.innerHTML = payload;
          el.style.color = payload === "online" ? "green" : "red";
        }
      }

      // Data JSON dari node
      if (topic === "SmIr/data") {
        try {
          const data = JSON.parse(payload);
          const node = data.Node;
          const fields = ["tegangan", "arus", "waterlvA", "waterlvB", "flowrate", "totalwater", "rssi"];
          fields.forEach(f => {
            const el = document.getElementById(`node${node}-${f}`);
            if (el) el.innerHTML = data[f];
          });
        } catch (e) {
          console.error("Error parsing JSON:", e);
        }
      }
    });

    function publishLamp() {
      const data = document.getElementById("lampu1nyala").checked ? "nyala" : "mati";
      client.publish("kelasiottt/12345678/lampu", data, { qos: 1, retain: true });
    }
  </script>
</body>
