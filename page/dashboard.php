<?php
// Pastikan $connection sudah tersedia dari file konfigurasi (tidak saya ubah).
// Contoh: include 'config/database.php'; jika diperlukan.

$sql = "SELECT * FROM devices WHERE active='Yes'";
$result = mysqli_query($connection, $sql);

/* =========================
   ADDED: Ambil last data per node (1..4)
   Sesuaikan nama tabel/kolom jika berbeda
   ========================= */
$lastData = [];
for ($i = 1; $i <= 4; $i++) {
  $q = "SELECT tegangan, arus, waterlvA, waterlvB, flowrate, totalwater, rssi, waktu
        FROM sensor_data
        WHERE node_id = " . intval($i) . "
        ORDER BY waktu DESC
        LIMIT 1";
  $r = mysqli_query($connection, $q);
  if ($r && mysqli_num_rows($r) > 0) {
    $lastData[$i] = mysqli_fetch_assoc($r);
  } else {
    $lastData[$i] = null;
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>
  <!-- Masukkan CSS/Font Awesome/Bootstrap yang diperlukan (tidak saya ubah) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    /* Sedikit styling agar kartu terlihat rapi jika belum ada stylesheet utama */
    .card { margin-bottom: 15px; }
    .card-primary { border-top: 3px solid #007bff; }
    .card-success { border-top: 3px solid #28a745; }
    .card-warning { border-top: 3px solid #ffc107; }
    .card-danger { border-top: 3px solid #dc3545; }
    .bg-gray { background-color: #6c757d; color: #fff; }
    .btn.active { box-shadow: none; }
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
              <strong>Status MQTT:</strong> <span id="status" style="color:red;">Tidak Terhubung</span>
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

          <!-- Lamp Button -->
          <div class="row">
            <div class="col-lg-6">
              <div class="card card-lightblue">
                <div class="card-header">
                  <h3 class="card-title">Lamp Button</h3>
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
                  <h3 class="card-title">Lamp Button</h3>
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

          <!-- Monitoring Sensor -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card card-success">
                <div class="card-header">
                  <h3 class="card-title">Monitoring Sensor</h3>
                </div>
                <div class="card-body">
                  <div class="row">
                    <?php for ($i = 1; $i <= 4; $i++) { ?>
                      <div class="col-md-3">
                        <div class="card card-<?php echo ['primary', 'success', 'warning', 'danger'][$i - 1]; ?>">
                          <div class="card-header">
                            <h3 class="card-title">Node <?php echo $i; ?></h3>
                          </div>
                          <div class="card-body">
                            <p>Tegangan: <span id="node<?php echo $i; ?>-tegangan">-</span> V</p>
                            <p>Arus: <span id="node<?php echo $i; ?>-arus">-</span> mA</p>
                            <p>Water Level A: <span id="node<?php echo $i; ?>-waterlvA">-</span> cm</p>
                            <p>Water Level B: <span id="node<?php echo $i; ?>-waterlvB">-</span> cm</p>
                            <p>Flow Rate: <span id="node<?php echo $i; ?>-flowrate">-</span> L/min</p>
                            <p>Total Water: <span id="node<?php echo $i; ?>-totalwater">-</span> L</p>
                            <p>RSSI: <span id="node<?php echo $i; ?>-rssi">-</span> dBm</p>
                            <!-- Jika ingin menampilkan waktu DB, tambahkan elemen <p id="node{i}-lastupdate"></p> di sini -->
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
                <div class="card-header bg-primary text-white">
                  <h3 class="card-title">Monitoring Dashboard</h3>
                </div>
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
                          <td><?php echo htmlspecialchars($row['serial_number']); ?></td>
                          <td><?php echo htmlspecialchars($row['location']); ?></td>
                          <td style="color:red" id="SmIr/status/<?php echo htmlspecialchars($row['serial_number']); ?>">offline</td>
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

  <!-- =========================
       ADDED: Kirim lastDataFromDB ke JS dan fungsi applyLastData
       ========================= -->
  <script>
    // Data terakhir dari DB (null jika tidak ada)
    const lastDataFromDB = <?php echo json_encode($lastData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    // Penanda node yang sudah menerima update via MQTT (agar tidak ditimpa)
    const mqttReceivedNodes = {};

    // Terapkan last data dari DB ke elemen jika belum ada data MQTT
    function applyLastDataFromDB() {
      for (let i = 1; i <= 4; i++) {
        // jika sudah ada data realtime, skip
        if (mqttReceivedNodes[i]) continue;

        const row = lastDataFromDB[i];
        if (!row) continue;

        const setIfExists = (id, value) => {
          const el = document.getElementById(id);
          if (!el) return;
          // Hanya set jika masih default (tanda '-'), atau kosong
          const cur = (el.innerHTML || '').toString().trim();
          if (cur === '-' || cur === '' ) {
            el.innerHTML = value !== null && value !== undefined ? value : '-';
          }
        };

        setIfExists(`node${i}-tegangan`, row.tegangan ?? '-');
        setIfExists(`node${i}-arus`, row.arus ?? '-');
        setIfExists(`node${i}-waterlvA`, row.waterlvA ?? '-');
        setIfExists(`node${i}-waterlvB`, row.waterlvB ?? '-');
        setIfExists(`node${i}-flowrate`, row.flowrate ?? '-');
        setIfExists(`node${i}-totalwater`, row.totalwater ?? '-');
        setIfExists(`node${i}-rssi`, row.rssi ?? '-');

        // (Opsional) jika mau menampilkan waktu update terakhir,
        // tambahkan elemen <p id="node{i}-lastupdate">-</p> di HTML dan uncomment:
        // setIfExists(`node${i}-lastupdate`, row.waktu ?? '-');
      }
    }

    // Pastikan dipanggil ketika DOM siap.
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", applyLastDataFromDB);
    } else {
      applyLastDataFromDB();
    }
  </script>

  <!-- MQTT Script -->
  <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>

  <script>
    const clientId = Math.random().toString(16).substr(2, 8);
    const host = 'wss://broker.emqx.io:8084/mqtt';

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
      client.subscribe("SmIr/status/#", { qos: 1 }); // subscribe semua status
    });

    client.on("message", function(topic, payload) {
      payload = payload.toString();

      // Sensor dasar
      if (topic === "kelasiottt/12345678/temperature") {
        const el = document.getElementById("temperature");
        if (el) el.innerHTML = payload;
      } else if (topic === "kelasiottt/12345678/humidity") {
        const el = document.getElementById("humidity");
        if (el) el.innerHTML = payload;
      } else if (topic === "kelasiottt/12345678/potentiometer") {
        const el = document.getElementById("potentiometer");
        if (el) el.innerHTML = payload;
      } else if (topic === "kelasiottt/12345678/lampu") {
        if (payload === "nyala") {
          const a = document.getElementById("label-lampu1-nyala");
          const b = document.getElementById("label-lampu1-mati");
          if (a) a.classList.add("active");
          if (b) b.classList.remove("active");
        } else {
          const a = document.getElementById("label-lampu1-nyala");
          const b = document.getElementById("label-lampu1-mati");
          if (a) a.classList.remove("active");
          if (b) b.classList.add("active");
        }
      }

      // Update status device dinamis
      if (topic.startsWith("SmIr/status/")) {
        const el = document.getElementById(topic);
        if (el) {
          el.innerHTML = payload;
          el.style.color = payload === "online" ? "green" : "red";
        }
      }

      // Data node JSON (SmIr/data)
      if (topic === "SmIr/data") {
        try {
          const data = JSON.parse(payload);
          // Node bisa berupa string/number, gunakan parseInt aman
          const nodeRaw = data.Node ?? data.node ?? data.node_id;
          const node = parseInt(nodeRaw);
          if (!node || isNaN(node)) {
            console.warn("Node invalid di payload SmIr/data:", nodeRaw);
            return;
          }

          // Tandai node ini sudah menerima data realtime, supaya last DB tidak menimpa
          mqttReceivedNodes[node] = true;

          // Update field jika elemen ada
          const trySet = (id, value) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML = value !== undefined && value !== null ? value : '-';
          };

          trySet(`node${node}-tegangan`, data.tegangan ?? data.voltage ?? '-');
          trySet(`node${node}-arus`, data.arus ?? data.current ?? '-');
          trySet(`node${node}-waterlvA`, data.waterlvA ?? data.waterLevelA ?? '-');
          trySet(`node${node}-waterlvB`, data.waterlvB ?? data.waterLevelB ?? '-');
          trySet(`node${node}-flowrate`, data.flowrate ?? data.flowRate ?? '-');
          trySet(`node${node}-totalwater`, data.totalwater ?? data.totalWater ?? '-');
          trySet(`node${node}-rssi`, data.rssi ?? '-');

          console.log(`Data diterima untuk Node ${node}:`, data);
        } catch (e) {
          console.error("Error parsing JSON SmIr/data:", e);
        }
      }
    });

    /**
     * publishLamp(el)
     * dipanggil dari onchange radio button (passing element this)
     * akan publish ke topic kelasiottt/12345678/lampu dengan payload 'nyala' atau 'mati'
     * (saya pertahankan topik sebelumnya agar kompatibel)
     */
    function publishLamp(el) {
      if (!client || !client.connected) {
        console.warn("MQTT client belum terhubung.");
        return;
      }
      try {
        const name = el.name; // 'lampu1' atau 'lampu2'
        // cari radio ter-check untuk grup ini
        const checked = document.querySelector(`input[name="${name}"]:checked`);
        if (!checked) return;
        const data = checked.id.includes('nyala') ? "nyala" : "mati";
        client.publish("kelasiottt/12345678/lampu", data, { qos: 1, retain: true });
      } catch (err) {
        console.error("publishLamp error:", err);
      }
    }
  </script>
