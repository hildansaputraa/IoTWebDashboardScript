<?php
include("koneksi.php");

// Ambil daftar device aktif
$sql = "SELECT * FROM devices WHERE active='Yes'";
$result = mysqli_query($connection, $sql);

// Ambil waktu terakhir dari setiap Node
$lastUpdates = [];
$queryLast = "SELECT node, MAX(created_at) AS last_update FROM data GROUP BY node";
$resultLast = mysqli_query($connection, $queryLast);
while ($row = mysqli_fetch_assoc($resultLast)) {
  $lastUpdates[$row['node']] = $row['last_update'];
}
?>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Dashboard</h1>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="?page=dashboard">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <!-- Main content -->
      <div class="content">
        <div class="container-fluid">

          <!-- Row pertama: Potentiometer, Temperature, Humidity -->
          <div class="row">
            <div class="col-lg-4">
              <div class="small-box bg-warning">
                <div class="inner">
                  <h3 id="potentiometer">-</h3>
                  <p>Potentiometer</p>
                </div>
                <div class="icon">
                  <i class="fas fa-tachometer-alt"></i>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="small-box bg-danger">
                <div class="inner">
                  <h3 id="temperature">-</h3>
                  <p>Temperature</p>
                </div>
                <div class="icon">
                  <i class="fas fa-temperature-high"></i>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="small-box bg-gray">
                <div class="inner">
                  <h3><span id="humidity">-</span>%</h3>
                  <p>Humidity</p>
                </div>
                <div class="icon">
                  <i class="fas fa-water"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Row kedua: Lamp Button -->
          <div class="row">
            <div class="col-lg-6">
              <div class="card card-lightblue">
                <div class="card-header">
                  <h3 class="card-title">Lamp Button 1</h3>
                </div>
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
                <div class="card-header">
                  <h3 class="card-title">Lamp Button 2</h3>
                </div>
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

          <!-- Monitoring Sensor Node 1-4 -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card card-success">
                <div class="card-header">
                  <h3 class="card-title">Monitoring Sensor</h3>
                </div>
                <div class="card-body">
                  <div class="row">

                    <!-- Node 1 -->
                    <div class="col-md-3">
                      <div class="card card-primary">
                        <div class="card-header">
                          <h3 class="card-title">Node 1</h3>
                        </div>
                        <div class="card-body">
                          <p>Tegangan: <span id="node1-tegangan">-</span> V</p>
                          <p>Arus: <span id="node1-arus">-</span> mA</p>
                          <p>Water Level A: <span id="node1-waterlvA">-</span> cm</p>
                          <p>Water Level B: <span id="node1-waterlvB">-</span> cm</p>
                          <p>Flow Rate: <span id="node1-flowrate">-</span> L/min</p>
                          <p>Total Water: <span id="node1-totalwater">-</span> L</p>
                          <p>RSSI: <span id="node1-rssi">-</span> dBm</p>
                          <hr>
                          <small class="text-muted">
                            Terakhir update:
                            <?php echo isset($lastUpdates['Node1']) ? $lastUpdates['Node1'] : 'Belum ada data'; ?>
                          </small>
                        </div>
                      </div>
                    </div>

                    <!-- Node 2 -->
                    <div class="col-md-3">
                      <div class="card card-success">
                        <div class="card-header">
                          <h3 class="card-title">Node 2</h3>
                        </div>
                        <div class="card-body">
                          <p>Tegangan: <span id="node2-tegangan">-</span> V</p>
                          <p>Arus: <span id="node2-arus">-</span> mA</p>
                          <p>Water Level A: <span id="node2-waterlvA">-</span> cm</p>
                          <p>Water Level B: <span id="node2-waterlvB">-</span> cm</p>
                          <p>Flow Rate: <span id="node2-flowrate">-</span> L/min</p>
                          <p>Total Water: <span id="node2-totalwater">-</span> L</p>
                          <p>RSSI: <span id="node2-rssi">-</span> dBm</p>
                          <hr>
                          <small class="text-muted">
                            Terakhir update:
                            <?php echo isset($lastUpdates['Node2']) ? $lastUpdates['Node2'] : 'Belum ada data'; ?>
                          </small>
                        </div>
                      </div>
                    </div>

                    <!-- Node 3 -->
                    <div class="col-md-3">
                      <div class="card card-warning">
                        <div class="card-header">
                          <h3 class="card-title">Node 3</h3>
                        </div>
                        <div class="card-body">
                          <p>Tegangan: <span id="node3-tegangan">-</span> V</p>
                          <p>Arus: <span id="node3-arus">-</span> mA</p>
                          <p>Water Level A: <span id="node3-waterlvA">-</span> cm</p>
                          <p>Water Level B: <span id="node3-waterlvB">-</span> cm</p>
                          <p>Flow Rate: <span id="node3-flowrate">-</span> L/min</p>
                          <p>Total Water: <span id="node3-totalwater">-</span> L</p>
                          <p>RSSI: <span id="node3-rssi">-</span> dBm</p>
                          <hr>
                          <small class="text-muted">
                            Terakhir update:
                            <?php echo isset($lastUpdates['Node3']) ? $lastUpdates['Node3'] : 'Belum ada data'; ?>
                          </small>
                        </div>
                      </div>
                    </div>

                    <!-- Node 4 -->
                    <div class="col-md-3">
                      <div class="card card-danger">
                        <div class="card-header">
                          <h3 class="card-title">Node 4</h3>
                        </div>
                        <div class="card-body">
                          <p>Tegangan: <span id="node4-tegangan">-</span> V</p>
                          <p>Arus: <span id="node4-arus">-</span> mA</p>
                          <p>Water Level A: <span id="node4-waterlvA">-</span> cm</p>
                          <p>Water Level B: <span id="node4-waterlvB">-</span> cm</p>
                          <p>Flow Rate: <span id="node4-flowrate">-</span> L/min</p>
                          <p>Total Water: <span id="node4-totalwater">-</span> L</p>
                          <p>RSSI: <span id="node4-rssi">-</span> dBm</p>
                          <hr>
                          <small class="text-muted">
                            Terakhir update:
                            <?php echo isset($lastUpdates['Node4']) ? $lastUpdates['Node4'] : 'Belum ada data'; ?>
                          </small>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Gambar Monitoring Dashboard -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card card-default">
                <div class="card-header bg-primary text-white">
                  <h3 class="card-title">Monitoring Dashboard</h3>
                </div>
                <div class="card-body text-center">
                  <div class="row">
                    <div class="col-md-12">
                      <img src="dist/img/maps.jpg" alt="Network Layout" class="img-fluid rounded" style="max-height: 300px;">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Devices Status -->
          <div class="row">
            <div class="col-12">
              <div class="card card-lightblue">
                <div class="card-header">
                  <h3 class="card-title">Devices Status</h3>
                </div>
                <div class="card-body table-responsive p-0" style="height: 300px;">
                  <table class="table table-head-fixed text-nowrap">
                    <thead>
                      <tr>
                        <th>Serial Number</th>
                        <th>Location</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                          <td><?php echo $row['serial_number'] ?></td>
                          <td><?php echo $row['location'] ?></td>
                          <td style="color:red" id="SmIr/status/<?php echo $row['serial_number']?>">offline</td>
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

  <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
  <script>
  const clientId = Math.random().toString(16).substr(2, 8)
  const host = 'wss://broker.emqx.io:8084/mqtt'

  const options = {
    keepalive: 30,
    clientId: clientId,
    username: "emqx_test",
    password: "emqx_test",
    protocolId: 'MQTT',
    protocolVersion: 4,
    clean: true,
    reconnectPeriod: 1000,
    connectTimeout: 30 * 1000,
  }

  console.log("Menghubungkan ke broker...");
  const client = mqtt.connect(host, options);

  client.on("connect", () => {
    console.log("Berhasil Connect");
    client.subscribe("kelasiottt/#", { qos: 1 });
    client.subscribe("SmIr/data", { qos: 1 });
  });

  client.on("message", function(topic, payload) {
    if (topic === "kelasiottt/12345678/temperature") {
      document.getElementById("temperature").innerHTML = payload;
    } else if (topic === "kelasiottt/12345678/humidity") {
      document.getElementById("humidity").innerHTML = payload;
    } else if (topic === "kelasiottt/12345678/potentiometer") {
      document.getElementById("potentiometer").innerHTML = payload;
    } else if (topic === "kelasiottt/12345678/lampu") {
      if (payload == "nyala") {
        document.getElementById("label-lampu1-nyala").classList.add("active");
        document.getElementById("label-lampu1-mati").classList.remove("active");
      } else {
        document.getElementById("label-lampu1-nyala").classList.remove("active");
        document.getElementById("label-lampu1-mati").classList.add("active");
      }
    }

    if (topic.includes("kelasiottt/status/12345678")) {
      document.getElementById(topic).innerHTML = payload;
      document.getElementById(topic).style.color = (payload.toString() === "online") ? "green" : "red";
    }

    // 🔹 Terima data JSON dari topic SmIr/data
    if (topic === "SmIr/data") {
      try {
        const data = JSON.parse(payload.toString());
        const node = data.Node;

        document.getElementById(`node${node}-tegangan`).innerHTML = data.tegangan;
        document.getElementById(`node${node}-arus`).innerHTML = data.arus;
        document.getElementById(`node${node}-waterlvA`).innerHTML = data.waterlvA;
        document.getElementById(`node${node}-waterlvB`).innerHTML = data.waterlvB;
        document.getElementById(`node${node}-flowrate`).innerHTML = data.flowrate;
        document.getElementById(`node${node}-totalwater`).innerHTML = data.totalwater;
        document.getElementById(`node${node}-rssi`).innerHTML = data.rssi;

        console.log(`Data diterima untuk Node ${node}:`, data);

        // 🔁 Tambahkan pembaruan timestamp otomatis (hh:mm:ss)
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        });

        // Ubah teks "Terakhir update:" di card node terkait
        const cardNode = document.querySelector(`#node${node}-rssi`).closest('.card-body');
        if (cardNode) {
          let smallTag = cardNode.querySelector('small.text-muted');
          if (smallTag) {
            smallTag.innerHTML = "Terakhir update: " + timeString;
          }
        }
      } catch (error) {
        console.error("Error parsing JSON:", error);
      }
    }
  });

  function publishLamp(value) {
    let data = "";
    if (document.getElementById("lampu1nyala").checked) data = "nyala";
    if (document.getElementById("lampu1mati").checked) data = "mati";
    client.publish("kelasiottt/12345678/lampu", data, { qos: 1, retain: true });
  }
</script>

</body>
