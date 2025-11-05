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
        <div class="col-lg-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <?php
              $flow3 = isset($fallback[3]['flowrate']) ? floatval($fallback[3]['flowrate']) : 0;
              $flow4 = isset($fallback[4]['flowrate']) ? floatval($fallback[4]['flowrate']) : 0;
              $avgFlow = ($flow3 + $flow4) / 2;
              ?>
              <h3 id="flowrate"><?php echo $avgFlow ? number_format($avgFlow, 2) : '-' ?></h3>
              <p>FlowRate</p>
            </div>
            <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
          </div>
        </div>
        <!-- <div class="col-lg-">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3 id="temperature"><?php echo $fallback['12345678']['temperature'] ?? '-' ?></h3>
              <p>Temperature</p>
            </div>
            <div class="icon"><i class="fas fa-temperature-high"></i></div>
          </div>
        </div> -->
        <div class="col-lg-6">
          <div class="small-box bg-gray">
            <div class="inner">
            <?php
            $total3 = isset($fallback[3]['totalwater']) ? floatval($fallback[3]['totalwater']) : 0;
            $total4 = isset($fallback[4]['totalwater']) ? floatval($fallback[4]['totalwater']) : 0;
            $totalWaterSum = $total3 + $total4;
            ?>
            <h3><span id="totalwater"><?php echo number_format($totalWaterSum, 2) ?></span> L</h3>
            <p>Total Water</p>
            </div>
            <div class="icon"><i class="fas fa-water"></i></div>
          </div>
        </div>
      </div>

      <!-- Solenoid Control dengan Mode -->
      <div class="row">
        <!-- Solenoid 1 -->
        <div class="col-lg-6">
          <div class="card card-lightblue">
            <div class="card-header"><h3 class="card-title">Solenoid 1</h3></div>
            <div class="card-body">
              <!-- Mode Selection -->
              <div class="form-group text-center">
                <label><strong>Mode Control:</strong></label>
                <div class="btn-group btn-group-toggle mx-auto d-block" style="max-width: 320px;" data-toggle="buttons">
                  <label class="btn btn-outline-primary active flex-fill" id="label-solenoid1-manual">
                    <input type="radio" name="mode-solenoid1" value="manual" id="mode-solenoid1-manual" checked onchange="changeSolenoidMode(1, this.value)"> Manual
                  </label>
                  <label class="btn btn-outline-success flex-fill" id="label-solenoid1-auto">
                    <input type="radio" name="mode-solenoid1" value="auto" id="mode-solenoid1-auto" onchange="changeSolenoidMode(1, this.value)"> Otomatis
                  </label>
                </div>
              </div>

              <!-- Manual Control -->
              <div id="manual-control-solenoid1" class="text-center mt-3">
                <div class="btn-group btn-group-toggle mx-auto d-inline-block" style="min-width: 200px;" data-toggle="buttons">
                  <label class="btn btn-success flex-fill" id="label-solenoid1-on">
                    <input type="radio" name="solenoid1" onchange="publishSolenoid(1, 1)" id="solenoid1on" autocomplete="off"> ON
                  </label>
                  <label class="btn btn-danger flex-fill active" id="label-solenoid1-off">
                    <input type="radio" name="solenoid1" onchange="publishSolenoid(1, 0)" id="solenoid1off" autocomplete="off" checked> OFF
                  </label>
                </div>
              </div>

              <!-- Auto Control Settings -->
              <div id="auto-control-solenoid1" style="display: none;">
                <div class="alert alert-info">
                  <i class="fas fa-info-circle"></i> Mode otomatis aktif. Solenoid akan ON ketika SEMUA NODE memenuhi threshold (Water Level A DAN B).
                </div>
                
                <div class="row">
                  <!-- Node 1 -->
                  <div class="col-md-6">
                    <div class="card mb-3 border-primary">
                      <div class="card-header bg-primary text-white">
                        <strong>Node 1 Threshold</strong>
                      </div>
                      <div class="card-body">
                        <div class="form-group">
                          <label>Water Level A (cm):</label>
                          <input type="number" class="form-control" id="threshold-node1-waterlvA-solenoid1" placeholder="Contoh: 50" step="0.1">
                        </div>
                        <div class="form-group">
                          <label>Water Level B (cm):</label>
                          <input type="number" class="form-control" id="threshold-node1-waterlvB-solenoid1" placeholder="Contoh: 30" step="0.1">
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Node 2 -->
                  <div class="col-md-6">
                    <div class="card mb-3 border-success">
                      <div class="card-header bg-success text-white">
                        <strong>Node 2 Threshold</strong>
                      </div>
                      <div class="card-body">
                        <div class="form-group">
                          <label>Water Level A (cm):</label>
                          <input type="number" class="form-control" id="threshold-node2-waterlvA-solenoid1" placeholder="Contoh: 45" step="0.1">
                        </div>
                        <div class="form-group">
                          <label>Water Level B (cm):</label>
                          <input type="number" class="form-control" id="threshold-node2-waterlvB-solenoid1" placeholder="Contoh: 25" step="0.1">
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Node 3 -->
                  <div class="col-md-6">
                    <div class="card mb-3 border-warning">
                      <div class="card-header bg-warning text-white">
                        <strong>Node 3 Threshold</strong>
                      </div>
                      <div class="card-body">
                        <div class="form-group">
                          <label>Water Level A (cm):</label>
                          <input type="number" class="form-control" id="threshold-node3-waterlvA-solenoid1" placeholder="Contoh: 55" step="0.1">
                        </div>
                        <div class="form-group">
                          <label>Water Level B (cm):</label>
                          <input type="number" class="form-control" id="threshold-node3-waterlvB-solenoid1" placeholder="Contoh: 35" step="0.1">
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Node 4 -->
                  <div class="col-md-6">
                    <div class="card mb-3 border-danger">
                      <div class="card-header bg-danger text-white">
                        <strong>Node 4 Threshold</strong>
                      </div>
                      <div class="card-body">
                        <div class="form-group">
                          <label>Water Level A (cm):</label>
                          <input type="number" class="form-control" id="threshold-node4-waterlvA-solenoid1" placeholder="Contoh: 48" step="0.1">
                        </div>
                        <div class="form-group">
                          <label>Water Level B (cm):</label>
                          <input type="number" class="form-control" id="threshold-node4-waterlvB-solenoid1" placeholder="Contoh: 28" step="0.1">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Aksi saat SEMUA node memenuhi threshold:</label>
                  <select class="form-control" id="action-solenoid1">
                    <option value="1">Nyalakan Solenoid (ON)</option>
                    <option value="0">Matikan Solenoid (OFF)</option>
                  </select>
                </div>
                <div class="text-center mt-3">
                  <button type="button" class="btn btn-primary btn-lg" onclick="saveAutoSettings(1)">
                    <i class="fas fa-save"></i> Simpan Pengaturan Otomatis
                  </button>
                </div>
                <div class="alert alert-warning mt-3">
                  <strong>Cara kerja:</strong> Isi threshold untuk setiap node (total 8 input). Klik tombol "Simpan Pengaturan Otomatis" untuk mengirim konfigurasi ke sistem. Solenoid akan berubah status hanya jika SEMUA node memenuhi kedua threshold (Water Level A DAN Water Level B) yang ditentukan.
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Solenoid 2 -->
        <div class="col-lg-6">
          <div class="card card-lightblue">
            <div class="card-header"><h3 class="card-title">Solenoid 2</h3></div>
            <div class="card-body">

              <!-- Mode Selection -->
              <div class="form-group text-center">
                <label><strong>Mode Control:</strong></label>
                <div class="btn-group btn-group-toggle mx-auto d-block" style="max-width: 320px;" data-toggle="buttons">
                  <label class="btn btn-outline-primary active flex-fill" id="label-solenoid2-manual">
                    <input type="radio" name="mode-solenoid2" value="manual" id="mode-solenoid2-manual" checked onchange="changeSolenoidMode(2, this.value)"> Manual
                  </label>
                  <label class="btn btn-outline-success flex-fill" id="label-solenoid2-auto">
                    <input type="radio" name="mode-solenoid2" value="auto" id="mode-solenoid2-auto" onchange="changeSolenoidMode(2, this.value)"> Otomatis
                  </label>
                </div>
              </div>

              <!-- Manual Control -->
              <div id="manual-control-solenoid2" class="text-center mt-3">
                <div class="btn-group btn-group-toggle mx-auto d-inline-block" style="min-width: 200px;" data-toggle="buttons">
                  <label class="btn btn-success flex-fill" id="label-solenoid2-on">
                    <input type="radio" name="solenoid2" onchange="publishSolenoid(2, 1)" id="solenoid2on" autocomplete="off"> ON
                  </label>
                  <label class="btn btn-danger flex-fill active" id="label-solenoid2-off">
                    <input type="radio" name="solenoid2" onchange="publishSolenoid(2, 0)" id="solenoid2off" autocomplete="off" checked> OFF
                  </label>
                </div>
              </div>

              <!-- Auto Control Settings -->
              <div id="auto-control-solenoid2" style="display: none;">
                <div class="alert alert-info">
                  <i class="fas fa-info-circle"></i> Mode otomatis aktif. Solenoid akan ON ketika SEMUA NODE memenuhi threshold (Water Level A DAN B).
                </div>
                
                <div class="row">
                  <!-- Node 1 -->
                  <div class="col-md-6">
                    <div class="card mb-3 border-primary">
                      <div class="card-header bg-primary text-white">
                        <strong>Node 1 Threshold</strong>
                      </div>
                      <div class="card-body">
                        <div class="form-group">
                          <label>Water Level A (cm):</label>
                          <input type="number" class="form-control" id="threshold-node1-waterlvA-solenoid2" placeholder="Contoh: 50" step="0.1">
                        </div>
                        <div class="form-group">
                          <label>Water Level B (cm):</label>
                          <input type="number" class="form-control" id="threshold-node1-waterlvB-solenoid2" placeholder="Contoh: 30" step="0.1">
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Node 2 -->
                  <div class="col-md-6">
                    <div class="card mb-3 border-success">
                      <div class="card-header bg-success text-white">
                        <strong>Node 2 Threshold</strong>
                      </div>
                      <div class="card-body">
                        <div class="form-group">
                          <label>Water Level A (cm):</label>
                          <input type="number" class="form-control" id="threshold-node2-waterlvA-solenoid2" placeholder="Contoh: 45" step="0.1">
                        </div>
                        <div class="form-group">
                          <label>Water Level B (cm):</label>
                          <input type="number" class="form-control" id="threshold-node2-waterlvB-solenoid2" placeholder="Contoh: 25" step="0.1">
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Node 3 -->
                  <div class="col-md-6">
                    <div class="card mb-3 border-warning">
                      <div class="card-header bg-warning text-white">
                        <strong>Node 3 Threshold</strong>
                      </div>
                      <div class="card-body">
                        <div class="form-group">
                          <label>Water Level A (cm):</label>
                          <input type="number" class="form-control" id="threshold-node3-waterlvA-solenoid2" placeholder="Contoh: 55" step="0.1">
                        </div>
                        <div class="form-group">
                          <label>Water Level B (cm):</label>
                          <input type="number" class="form-control" id="threshold-node3-waterlvB-solenoid2" placeholder="Contoh: 35" step="0.1">
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Node 4 -->
                  <div class="col-md-6">
                    <div class="card mb-3 border-danger">
                      <div class="card-header bg-danger text-white">
                        <strong>Node 4 Threshold</strong>
                      </div>
                      <div class="card-body">
                        <div class="form-group">
                          <label>Water Level A (cm):</label>
                          <input type="number" class="form-control" id="threshold-node4-waterlvA-solenoid2" placeholder="Contoh: 48" step="0.1">
                        </div>
                        <div class="form-group">
                          <label>Water Level B (cm):</label>
                          <input type="number" class="form-control" id="threshold-node4-waterlvB-solenoid2" placeholder="Contoh: 28" step="0.1">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Aksi saat SEMUA node memenuhi threshold:</label>
                  <select class="form-control" id="action-solenoid2">
                    <option value="1">Nyalakan Solenoid (ON)</option>
                    <option value="0">Matikan Solenoid (OFF)</option>
                  </select>
                </div>

                <!-- TOMBOL SIMPAN — RATA TENGAH -->
                <div class="text-center mt-4">
                  <button type="button" class="btn btn-primary btn-lg px-5 shadow-sm" onclick="saveAutoSettings(2)">
                    <i class="fas fa-save"></i> <strong>Simpan Pengaturan Otomatis</strong>
                  </button>
                </div>

                <div class="alert alert-warning mt-3">
                  <strong>Cara kerja:</strong> Isi threshold untuk setiap node (total 8 input). Klik tombol "Simpan Pengaturan Otomatis" untuk mengirim konfigurasi ke sistem. Solenoid akan berubah status hanya jika SEMUA node memenuhi kedua threshold (Water Level A DAN B) yang ditentukan.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
                <!-- TOMBOL DARURAT: MATIKAN SEMUA SOLENOID -->
      <div class="row mt-4">
        <div class="col-12 text-center">
          <button type="button" class="btn btn-danger btn-lg shadow-lg px-5" onclick="forceOffAll()">
            <i class="fas fa-power-off"></i> <strong>MATIKAN SEMUA SOLENOID</strong>
          </button>
          <p class="text-muted mt-2"><small>Mode 0: Solenoid 1 & 2 langsung mati</small></p>
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
                  setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'Indonesian_indonesia.1252');
                  $queryTime = mysqli_query($connection, "SELECT MAX(created_at) AS last_time FROM data WHERE node = $i");
                  $timeRow = mysqli_fetch_assoc($queryTime);
                  if ($timeRow && $timeRow['last_time']) {
                    $timestamp = strtotime($timeRow['last_time']);
                    $lastTime = strftime('%e %B %Y %H:%M:%S', $timestamp);
                  } else {
                    $lastTime = '-';
                  }
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

  // State untuk mode solenoid
  const solenoidState = {
    1: { mode: 'manual', currentState: 0 },
    2: { mode: 'manual', currentState: 0 }
  };

  // Data sensor terbaru dari setiap node
  const sensorData = {
    1: { waterlvA: 0, waterlvB: 0 },
    2: { waterlvA: 0, waterlvB: 0 },
    3: { waterlvA: 0, waterlvB: 0 },
    4: { waterlvA: 0, waterlvB: 0 }
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
        
        // Update UI
        fields.forEach(f => {
          const el = document.getElementById(`node${node}-${f}`);
          if (el) el.innerHTML = data[f];
        });

        // Simpan data sensor untuk logic otomatis
        if (sensorData[node]) {
          sensorData[node].waterlvA = parseFloat(data.waterlvA) || 0;
          sensorData[node].waterlvB = parseFloat(data.waterlvB) || 0;
        }

        // Check automatic control untuk kedua solenoid
        // checkAutoControl(1);
        // checkAutoControl(2);

      } catch (e) {
        console.error("Error parsing JSON:", e);
      }
    }
  });

  // Fungsi untuk mengganti mode solenoid
  function changeSolenoidMode(solenoidNum, mode) {
    solenoidState[solenoidNum].mode = mode;
    
    const manualDiv = document.getElementById(`manual-control-solenoid${solenoidNum}`);
    const autoDiv = document.getElementById(`auto-control-solenoid${solenoidNum}`);
    
    if (mode === 'manual') {
      manualDiv.style.display = 'block';
      autoDiv.style.display = 'none';
    } else {
      manualDiv.style.display = 'none';
      autoDiv.style.display = 'block';
      // Langsung check kondisi saat mode auto diaktifkan
      checkAutoControl(solenoidNum);
    }
  }

  // Fungsi untuk publish solenoid manual
  function publishSolenoid(solenoidNum, state) {
    if (solenoidState[solenoidNum].mode !== 'manual') return;
    
    const controlData = {
      mode: 1
    };
    
    if (solenoidNum === 1) {
      controlData.solenoidSatu = state;
    } else {
      controlData.solenoidDua = state;
    }
    
    solenoidState[solenoidNum].currentState = state;
    
    client.publish("SmIr/control", JSON.stringify(controlData), { qos: 1, retain: true });
    console.log("Published manual control:", controlData);
    
    // Simpan ke database via AJAX
    saveToDatabase('manual', solenoidNum, controlData);
  }

  // Fungsi untuk menyimpan pengaturan otomatis
  function saveAutoSettings(solenoidNum) {
      const autoSettings = { mode: 2 };

      let hasData = false;
      for (let nodeNum = 1; nodeNum <= 4; nodeNum++) {
          const aInput = document.getElementById(`threshold-node${nodeNum}-waterlvA-solenoid${solenoidNum}`);
          const bInput = document.getElementById(`threshold-node${nodeNum}-waterlvB-solenoid${solenoidNum}`);
          
          const valA = aInput?.value.trim();
          const valB = bInput?.value.trim();

          if (valA !== '' && valB !== '' && !isNaN(valA) && !isNaN(valB)) {
              autoSettings[`waterlvA${nodeNum}`] = parseFloat(valA);
              autoSettings[`waterlvB${nodeNum}`] = parseFloat(valB);
              hasData = true;
          }
      }

      if (!hasData) {
          alert('Isi minimal satu pasang threshold!');
          return;
      }

      const actionSelect = document.getElementById(`action-solenoid${solenoidNum}`);
      autoSettings.action = actionSelect?.value || 'off';

      // Kirim solenoid number
      autoSettings.solenoid = solenoidNum;

      client.publish("SmIr/control", JSON.stringify(autoSettings), { qos: 1, retain: true });
      console.log("Published auto settings:", autoSettings);

      saveToDatabase('auto', solenoidNum, autoSettings);
  }
      // Fungsi untuk menyimpan ke database
  function saveToDatabase(type, solenoidNum, data) {
      fetch('page/save_actuator.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              type: type,
              solenoid: solenoidNum,
              data: data
          })
      })
      .then(response => response.json())
      .then(result => {
          if (result.status === 'success') {
              console.log(`Data ${type} solenoid ${solenoidNum} tersimpan di DB`);
          } else {
              console.error('Gagal simpan ke DB:', result.message);
              alert('Gagal menyimpan ke database: ' + result.message);
          }
      })
      .catch(error => {
          console.error('Error AJAX:', error);
          alert('Terjadi kesalahan jaringan saat menyimpan data.');
      });
  }
  function forceOffAll() {
    if (!confirm("Yakin matikan SEMUA solenoid?")) return;

    const payload = { mode: 0 };
    client.publish("SmIr/kontrol", JSON.stringify(payload), { qos: 1, retain: true });
    console.log("Semua solenoid dimatikan!");

    // GUNAKAN type: 'reset', solenoid: 0
    saveToDatabase('reset', 0, payload);
  }
</script>