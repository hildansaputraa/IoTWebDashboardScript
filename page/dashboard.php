<?php
// dashboard.php
include "config/database.php"; // pastikan $connection tersedia

// Ambil daftar device aktif
$sql = "SELECT * FROM devices WHERE active='Yes'";
$result = mysqli_query($connection, $sql);

// Ambil data terakhir dari database untuk fallback (indexed by node_id)
$fallback = [];
$fallbackQuery = mysqli_query($connection, "SELECT * FROM last_data ORDER BY timestamp DESC");
if ($fallbackQuery) {
  while ($row = mysqli_fetch_assoc($fallbackQuery)) {
    $node = $row['node_id'];
    // Simpan hanya satu (terbaru) per node — karena ORDER BY timestamp DESC,
    // pertama kali muncul per node adalah yang paling baru. Pastikan tidak
    // overwrite jika sudah ada.
    if (!isset($fallback[$node])) {
      $fallback[$node] = $row;
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard</title>
  <!-- Masukkan CSS AdminLTE/Bootstrap/fontawesome sesuai project-mu -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .card-lightblue { background:#e6f7ff; }
    .small-box.bg-gray { background:#6c757d; color:#fff; }
  </style>
</head>
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
            <div class="col-sm-6 text-right">
              <!-- Elemen status untuk MQTT -->
              <span id="status" style="font-weight:bold;color:orange">Belum Terhubung</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main content -->
      <div class="content">
        <div class="container-fluid">

          <div class="row">
            <div class="col-lg-4">
              <div class="small-box bg-warning">
                <div class="inner">
                  <!-- Jika ada fallback (misal node id '12345678'), tampilkan -->
                  <h3 id="potentiometer"><?php echo isset($fallback['12345678']) && isset($fallback['12345678']['potentiometer']) ? htmlspecialchars($fallback['12345678']['potentiometer']) : '-'; ?></h3>
                  <p>Potentiometer</p>
                </div>
                <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="small-box bg-danger">
                <div class="inner">
                  <h3 id="temperature"><?php echo isset($fallback['12345678']) && isset($fallback['12345678']['temperature']) ? htmlspecialchars($fallback['12345678']['temperature']) : '-'; ?></h3>
                  <p>Temperature</p>
                </div>
                <div class="icon"><i class="fas fa-temperature-high"></i></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="small-box bg-gray">
                <div class="inner">
                  <h3><span id="humidity"><?php echo isset($fallback['12345678']) && isset($fallback['12345678']['humidity']) ? htmlspecialchars($fallback['12345678']['humidity']) : '-'; ?></span>%</h3>
                  <p>Humidity</p>
                </div>
                <div class="icon"><i class="fas fa-water"></i></div>
              </div>
            </div>
          </div>

          <!-- Lamp Buttons -->
          <div class="row">
            <div class="col-lg-6">
              <div class="card card-lightblue">
                <div class="card-header"><h3 class="card-title">Lamp Button 1</h3></div>
                <div class="card-body table-responsive pad">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-danger <?php // tidak menentukan active; MQTT akan update ?>">
                      <input type="radio" name="lampu1" onchange="publishLamp(this)" id="lampu1nyala" autocomplete="off"> On
                    </label>
                    <label class="btn btn-danger">
                      <input type="radio" name="lampu1" onchange="publishLamp(this)" id="lampu1mati" autocomplete="off"> Off
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <div class="card card-lightblue">
                <div class="card-header"><h3 class="card-title">Lamp Button 2</h3></div>
                <div class="card-body table-responsive pad">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-danger">
                      <input type="radio" name="lampu2" onchange="publishLamp(this)" id="lampu2nyala" autocomplete="off"> On
                    </label>
                    <label class="btn btn-danger">
                      <input type="radio" name="lampu2" onchange="publishLamp(this)" id="lampu2mati" autocomplete="off"> Off
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Monitoring Sensor (Nodes) -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card card-success">
                <div class="card-header"><h3 class="card-title">Monitoring Sensor</h3></div>
                <div class="card-body">
                  <div class="row">
                    <?php for ($i = 1; $i <= 4; $i++) {
                      // coba ambil fallback berdasarkan node numeric (1..4)
                      $fb = isset($fallback[$i]) ? $fallback[$i] : null;
                    ?>
                      <div class="col-md-3">
                        <div class="card card-<?php echo ['primary','success','warning','danger'][$i-1]; ?>">
                          <div class="card-header"><h3 class="card-title">Node <?php echo $i; ?></h3></div>
                          <div class="card-body">
                            <p>Tegangan: <span id="node<?php echo $i; ?>-tegangan"><?php echo $fb ? htmlspecialchars($fb['tegangan']) : '-'; ?></span> V</p>
                            <p>Arus: <span id="node<?php echo $i; ?>-arus"><?php echo $fb ? htmlspecialchars($fb['arus']) : '-'; ?></span> mA</p>
                            <p>Water Level A: <span id="node<?php echo $i; ?>-waterlvA"><?php echo $fb ? htmlspecialchars($fb['waterlvA']) : '-'; ?></span> cm</p>
                            <p>Water Level B: <span id="node<?php echo $i; ?>-waterlvB"><?php echo $fb ? htmlspecialchars($fb['waterlvB']) : '-'; ?></span> cm</p>
                            <p>Flow Rate: <span id="node<?php echo $i; ?>-flowrate"><?php echo $fb ? htmlspecialchars($fb['flowrate']) : '-'; ?></span> L/min</p>
                            <p>Total Water: <span id="node<?php echo $i; ?>-totalwater"><?php echo $fb ? htmlspecialchars($fb['totalwater']) : '-'; ?></span> L</p>
                            <p>RSSI: <span id="node<?php echo $i; ?>-rssi"><?php echo $fb ? htmlspecialchars($fb['rssi']) : '-'; ?></span> dBm</p>
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
                  <img src="dist/img/maps.jpg" alt="Network Layout" class="img-fluid rounded" style="max-height:300px;">
                </div>
              </div>
            </div>
          </div>

          <!-- Devices Status -->
          <div class="row">
            <div class="col-12">
              <div class="card card-lightblue">
                <div class="card-header"><h3 class="card-title">Devices Status</h3></div>
                <div class="card-body table-responsive p-0" style="height:300px;">
                  <table class="table table-head-fixed text-nowrap">
                    <thead>
                      <tr><th>Serial Number</th><th>Location</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                      <?php if ($result) {
                        while ($row = mysqli_fetch_assoc($result)) {
                          $sn = htmlspecialchars($row['serial_number']);
                          $loc = htmlspecialchars($row['location']);
                      ?>
                        <tr>
                          <td><?php echo $sn; ?></td>
                          <td><?php echo $loc; ?></td>
                          <!-- beri id yang sesuai topik SmIr/status/<serial_number> -->
                          <td style="color:red" id="<?php echo 'SmIr/status/' . $sn; ?>">
                            <?php
                              // jika ada fallback status di DB (mis. kolom 'status' pada last_data)
                              $stat = '-';
                              // coba cari fallback berdasarkan serial_number (opsional)
                              // contoh: last_data mungkin punya kolom serial_number
                              // if ($fallback_by_sn[$sn]) $stat = $fallback_by_sn[$sn]['status'];
                              echo $stat === null ? 'offline' : $stat;
                            ?>
                          </td>
                        </tr>
                      <?php }
                      } else { ?>
                        <tr><td colspan="3">Tidak ada device atau query gagal.</td></tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

        </div> <!-- /.container-fluid -->
      </div> <!-- /.content -->
    </div> <!-- /.content-wrapper -->
  </div> <!-- /.wrapper -->

  <!-- MQTT Script -->
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
      console.log("Berhasil connect ke broker!");
      const statusEl = document.getElementById("status");
      if (statusEl) { statusEl.innerHTML = "Terhubung"; statusEl.style.color = "green"; }

      client.subscribe("kelasiottt/#", { qos: 1 });
      client.subscribe("SmIr/data", { qos: 1 });
      client.subscribe("SmIr/status/#", { qos: 1 });
    });

    client.on("message", function(topic, payload) {
      payload = payload.toString();

      // Sensor dasar
      if (topic === "kelasiottt/12345678/temperature") document.getElementById("temperature").innerHTML = payload;
      else if (topic === "kelasiottt/12345678/humidity") document.getElementById("humidity").innerHTML = payload;
      else if (topic === "kelasiottt/12345678/potentiometer") document.getElementById("potentiometer").innerHTML = payload;
      else if (topic === "kelasiottt/12345678/lampu") {
        if (payload === "nyala") {
          document.getElementById("label-lampu1-nyala")?.classList.add("active");
          document.getElementById("label-lampu1-mati")?.classList.remove("active");
        } else {
          document.getElementById("label-lampu1-nyala")?.classList.remove("active");
          document.getElementById("label-lampu1-mati")?.classList.add("active");
        }
      }

      // Update status device dinamis
      if (topic.startsWith("SmIr/status/")) {
        // topic contoh: SmIr/status/<serial_number>
        const el = document.getElementById(topic);
        if (el) {
          el.innerHTML = payload;
          el.style.color = payload === "online" ? "green" : "red";
        } else {
          console.warn("Elemen status tidak ditemukan untuk topic:", topic);
        }
      }

      // Data node JSON
      if (topic === "SmIr/data") {
        try {
          const data = JSON.parse(payload);
          const node = data.Node;
          if (!node) return;
          document.getElementById(`node${node}-tegangan`).innerHTML = data.tegangan ?? '-';
          document.getElementById(`node${node}-arus`).innerHTML = data.arus ?? '-';
          document.getElementById(`node${node}-waterlvA`).innerHTML = data.waterlvA ?? '-';
          document.getElementById(`node${node}-waterlvB`).innerHTML = data.waterlvB ?? '-';
          document.getElementById(`node${node}-flowrate`).innerHTML = data.flowrate ?? '-';
          document.getElementById(`node${node}-totalwater`).innerHTML = data.totalwater ?? '-';
          document.getElementById(`node${node}-rssi`).innerHTML = data.rssi ?? '-';
        } catch (e) {
          console.error("Error parsing JSON:", e, "payload:", payload);
        }
      }
    });

    // publishLamp menerima elemen input (radio) yang berubah
    function publishLamp(el) {
      const name = el.name; // lampu1 atau lampu2
      // cari radio yang dipilih dari grup tersebut
      const checked = document.querySelector(`input[name="${name}"]:checked`);
      if (!checked) return;
      const value = checked.id.includes('nyala') ? 'nyala' : 'mati';
      // contoh topik: kelasiottt/12345678/lampu
      client.publish(`kelasiottt/12345678/${name}`, value, { qos: 1, retain: true });
    }
  </script>

  <!-- JS library bootstrap (opsional) -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
