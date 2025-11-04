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

      <!-- Main content -->
      <div class="content">
        <div class="container-fluid">

          <!-- Sensor dasar -->
          <div class="row">
            <div class="col-lg-4">
              <div class="small-box bg-warning">
                <div class="inner">
                  <h3 id="potentiometer"><?php echo htmlspecialchars($fallback['12345678']['potentiometer'] ?? '-') ?></h3>
                  <p>Potentiometer</p>
                </div>
                <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="small-box bg-danger">
                <div class="inner">
                  <h3 id="temperature"><?php echo htmlspecialchars($fallback['12345678']['temperature'] ?? '-') ?></h3>
                  <p>Temperature</p>
                </div>
                <div class="icon"><i class="fas fa-temperature-high"></i></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="small-box bg-gray">
                <div class="inner">
                  <h3><span id="humidity"><?php echo htmlspecialchars($fallback['12345678']['humidity'] ?? '-') ?></span>%</h3>
                  <p>Humidity</p>
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
                  <div class="form-group">
                    <label>Mode Kontrol:</label>
                    <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                      <label class="btn btn-outline-primary active" id="label-solenoid1-manual">
                        <input type="radio" name="mode-solenoid1" value="manual" id="mode-solenoid1-manual" checked onchange="changeSolenoidMode(1, this.value)"> Manual
                      </label>
                      <label class="btn btn-outline-success" id="label-solenoid1-auto">
                        <input type="radio" name="mode-solenoid1" value="auto" id="mode-solenoid1-auto" onchange="changeSolenoidMode(1, this.value)"> Otomatis
                      </label>
                    </div>
                  </div>

                  <!-- Manual Control -->
                  <div id="manual-control-solenoid1">
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                      <label class="btn btn-success" id="label-solenoid1-on">
                        <input type="radio" name="solenoid1" onchange="publishSolenoid(1, 1)" id="solenoid1on" autocomplete="off"> ON
                      </label>
                      <label class="btn btn-danger active" id="label-solenoid1-off">
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
                      <!-- Node thresholds (1..4) -->
                      <?php for ($node = 1; $node <= 4; $node++): ?>
                        <div class="col-md-6">
                          <div class="card mb-3 <?php echo ['border-primary','border-success','border-warning','border-danger'][$node-1]; ?>">
                            <div class="card-header <?php echo ['bg-primary','bg-success','bg-warning','bg-danger'][$node-1]; ?> text-white">
                              <strong>Node <?php echo $node ?> Threshold</strong>
                            </div>
                            <div class="card-body">
                              <div class="form-group">
                                <label>Water Level A (cm):</label>
                                <input type="number" class="form-control" id="threshold-node<?php echo $node ?>-waterlvA-solenoid1" placeholder="Contoh: 50" step="0.1">
                              </div>
                              <div class="form-group">
                                <label>Water Level B (cm):</label>
                                <input type="number" class="form-control" id="threshold-node<?php echo $node ?>-waterlvB-solenoid1" placeholder="Contoh: 30" step="0.1">
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php endfor; ?>
                    </div>

                    <div class="form-group">
                      <label>Aksi saat SEMUA node memenuhi threshold:</label>
                      <select class="form-control" id="action-solenoid1">
                        <option value="on">Nyalakan Solenoid (ON)</option>
                        <option value="off">Matikan Solenoid (OFF)</option>
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

            <!-- Solenoid 2 (mirip Solenoid 1) -->
            <div class="col-lg-6">
              <div class="card card-lightblue">
                <div class="card-header"><h3 class="card-title">Solenoid 2</h3></div>
                <div class="card-body">
                  <div class="form-group">
                    <label>Mode Kontrol:</label>
                    <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                      <label class="btn btn-outline-primary active" id="label-solenoid2-manual">
                        <input type="radio" name="mode-solenoid2" value="manual" id="mode-solenoid2-manual" checked onchange="changeSolenoidMode(2, this.value)"> Manual
                      </label>
                      <label class="btn btn-outline-success" id="label-solenoid2-auto">
                        <input type="radio" name="mode-solenoid2" value="auto" id="mode-solenoid2-auto" onchange="changeSolenoidMode(2, this.value)"> Otomatis
                      </label>
                    </div>
                  </div>

                  <div id="manual-control-solenoid2">
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                      <label class="btn btn-success" id="label-solenoid2-on">
                        <input type="radio" name="solenoid2" onchange="publishSolenoid(2, 1)" id="solenoid2on" autocomplete="off"> ON
                      </label>
                      <label class="btn btn-danger active" id="label-solenoid2-off">
                        <input type="radio" name="solenoid2" onchange="publishSolenoid(2, 0)" id="solenoid2off" autocomplete="off" checked> OFF
                      </label>
                    </div>
                  </div>

                  <div id="auto-control-solenoid2" style="display: none;">
                    <div class="alert alert-info">
                      <i class="fas fa-info-circle"></i> Mode otomatis aktif. Solenoid akan ON ketika SEMUA NODE memenuhi threshold (Water Level A DAN B).
                    </div>
                    <div class="row">
                      <?php for ($node = 1; $node <= 4; $node++): ?>
                        <div class="col-md-6">
                          <div class="card mb-3 <?php echo ['border-primary','border-success','border-warning','border-danger'][$node-1]; ?>">
                            <div class="card-header <?php echo ['bg-primary','bg-success','bg-warning','bg-danger'][$node-1]; ?> text-white">
                              <strong>Node <?php echo $node ?> Threshold</strong>
                            </div>
                            <div class="card-body">
                              <div class="form-group">
                                <label>Water Level A (cm):</label>
                                <input type="number" class="form-control" id="threshold-node<?php echo $node ?>-waterlvA-solenoid2" placeholder="Contoh: 50" step="0.1">
                              </div>
                              <div class="form-group">
                                <label>Water Level B (cm):</label>
                                <input type="number" class="form-control" id="threshold-node<?php echo $node ?>-waterlvB-solenoid2" placeholder="Contoh: 30" step="0.1">
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php endfor; ?>
                    </div>

                    <div class="form-group">
                      <label>Aksi saat SEMUA node memenuhi threshold:</label>
                      <select class="form-control" id="action-solenoid2">
                        <option value="on">Nyalakan Solenoid (ON)</option>
                        <option value="off">Matikan Solenoid (OFF)</option>
                      </select>
                    </div>
                    <div class="text-center mt-3">
                      <button type="button" class="btn btn-primary btn-lg" onclick="saveAutoSettings(2)">
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
                            <p>Tegangan: <span id="node<?php echo $i; ?>-tegangan"><?php echo htmlspecialchars($nodeData['tegangan'] ?? '-') ?></span> V</p>
                            <p>Arus: <span id="node<?php echo $i; ?>-arus"><?php echo htmlspecialchars($nodeData['arus'] ?? '-') ?></span> mA</p>
                            <p>Water Level A: <span id="node<?php echo $i; ?>-waterlvA"><?php echo htmlspecialchars($nodeData['waterlvA'] ?? '-') ?></span> cm</p>
                            <p>Water Level B: <span id="node<?php echo $i; ?>-waterlvB"><?php echo htmlspecialchars($nodeData['waterlvB'] ?? '-') ?></span> cm</p>
                            <p>Flow Rate: <span id="node<?php echo $i; ?>-flowrate"><?php echo htmlspecialchars($nodeData['flowrate'] ?? '-') ?></span> L/min</p>
                            <p>Total Water: <span id="node<?php echo $i; ?>-totalwater"><?php echo htmlspecialchars($nodeData['totalwater'] ?? '-') ?></span> L</p>
                            <p>RSSI: <span id="node<?php echo $i; ?>-rssi"><?php echo htmlspecialchars($nodeData['rssi'] ?? '-') ?></span> dBm</p>
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
                      <?php while ($row = mysqli_fetch_assoc($result)) { 
                        $statusId = 'SmIr_status_' . htmlspecialchars($row['serial_number']);
                      ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['serial_number']) ?></td>
                          <td><?php echo htmlspecialchars($row['location']) ?></td>
                          <td style="color:red" id="<?php echo $statusId ?>">offline</td>
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

      client.subscribe("kelasiottt/#", { qos: 1 });
      client.subscribe("SmIr/data", { qos: 1 });
      client.subscribe("SmIr/status/#", { qos: 1 });
    });

    client.on("reconnect", () => {
      console.log("Mencoba reconnect...");
      const statusEl = document.getElementById("status");
      if (statusEl) {
        statusEl.innerHTML = "Mencoba reconnect";
        statusEl.style.color = "orange";
      }
    });

    client.on("offline", () => {
      console.log("Client offline");
      const statusEl = document.getElementById("status");
      if (statusEl) {
        statusEl.innerHTML = "Offline";
        statusEl.style.color = "red";
      }
    });

    client.on("message", function(topic, payload) {
      payload = payload.toString();

      // topic match untuk sensor spesifik (contoh)
      if (topic === "kelasiottt/12345678/temperature") {
        const el = document.getElementById("temperature");
        if (el) el.innerHTML = payload;
      } else if (topic === "kelasiottt/12345678/humidity") {
        const el = document.getElementById("humidity");
        if (el) el.innerHTML = payload;
      } else if (topic === "kelasiottt/12345678/potentiometer") {
        const el = document.getElementById("potentiometer");
        if (el) el.innerHTML = payload;
      }

      // Update status perangkat (mapping topic -> element id)
      if (topic.startsWith("SmIr/status/")) {
        const mappedId = topic.replace(/\//g, '_'); // e.g. "SmIr_status_SERIAL"
        const el = document.getElementById(mappedId);
        if (el) {
          el.innerHTML = payload;
          el.style.color = payload === "online" ? "green" : "red";
        } else {
          // fallback: coba cari element with prefix SmIr_status_ + serial
          console.debug("Status element not found for topic:", topic);
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
            if (el && data[f] !== undefined) el.innerHTML = data[f];
          });

          // Simpan data sensor untuk logic otomatis (pastikan numeric)
          if (sensorData[node]) {
            sensorData[node].waterlvA = parseFloat(data.waterlvA) || 0;
            sensorData[node].waterlvB = parseFloat(data.waterlvB) || 0;
          }

          // Check automatic control untuk kedua solenoid
          checkAutoControl(1);
          checkAutoControl(2);

        } catch (e) {
          console.error("Error parsing JSON SmIr/data:", e, payload);
        }
      }
    });

    // Fungsi untuk mengganti mode solenoid
    function changeSolenoidMode(solenoidNum, mode) {
      solenoidState[solenoidNum].mode = mode;
      
      const manualDiv = document.getElementById(`manual-control-solenoid${solenoidNum}`);
      const autoDiv = document.getElementById(`auto-control-solenoid${solenoidNum}`);
      
      if (mode === 'manual') {
        if (manualDiv) manualDiv.style.display = 'block';
        if (autoDiv) autoDiv.style.display = 'none';
      } else {
        if (manualDiv) manualDiv.style.display = 'none';
        if (autoDiv) autoDiv.style.display = 'block';
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
      
      // Konsisten: topik publish semua kontrol = SmIr/kontrol
      client.publish("SmIr/kontrol", JSON.stringify(controlData), { qos: 1, retain: true });
      console.log("Published manual control:", controlData);
      
      // Simpan ke database via AJAX (type 'manual')
      saveToDatabase('manual', solenoidNum, controlData);
    }

    // Fungsi untuk menyimpan pengaturan otomatis (thresholds)
    function saveAutoSettings(solenoidNum) {
      const autoSettings = {
        mode: 2,
        solenoid: solenoidNum
      };
      
      // Ambil threshold untuk setiap node
      for (let nodeNum = 1; nodeNum <= 4; nodeNum++) {
        const thresholdA = document.getElementById(`threshold-node${nodeNum}-waterlvA-solenoid${solenoidNum}`);
        const thresholdB = document.getElementById(`threshold-node${nodeNum}-waterlvB-solenoid${solenoidNum}`);
        
        if (thresholdA && thresholdB) {
          const valA = parseFloat(thresholdA.value);
          const valB = parseFloat(thresholdB.value);
          
          if (!isNaN(valA)) {
            autoSettings[`waterlvA${nodeNum}`] = valA;
          }
          if (!isNaN(valB)) {
            autoSettings[`waterlvB${nodeNum}`] = valB;
          }
        }
      }
      
      // Tambahkan aksi
      const action = document.getElementById(`action-solenoid${solenoidNum}`);
      if (action) {
        autoSettings.action = action.value;
      }
      
      // Publish ke MQTT
      client.publish("SmIr/kontrol", JSON.stringify(autoSettings), { qos: 1, retain: true });
      console.log(`Published auto settings Solenoid ${solenoidNum}:`, autoSettings);
      
      // Simpan konfigurasi otomatis ke database (type 'auto')
      saveToDatabase('auto', solenoidNum, autoSettings);
      
      // Tampilkan notifikasi
      alert(`Pengaturan otomatis Solenoid ${solenoidNum} berhasil disimpan!`);
    }

    // Fungsi untuk menyimpan ke database via endpoint PHP
    function saveToDatabase(type, solenoidNum, data) {
      fetch('save_actuator.php', {
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
        console.log('Saved to database:', result);
      })
      .catch(error => {
        console.error('Error saving to database:', error);
      });
    }

    // Fungsi untuk load settings dari database saat halaman dimuat
    function loadSavedSettings() {
      fetch('get_actuator_settings.php')
        .then(response => response.json())
        .then(settings => {
          if (settings.success) {
            // Load manual state untuk solenoid 1
            if (settings.solenoid1_manual !== undefined) {
              const state = parseInt(settings.solenoid1_manual);
              if (state === 1) {
                const elOn = document.getElementById('solenoid1on');
                const lblOn = document.getElementById('label-solenoid1-on');
                const lblOff = document.getElementById('label-solenoid1-off');
                if (elOn) elOn.checked = true;
                if (lblOn) lblOn.classList.add('active');
                if (lblOff) lblOff.classList.remove('active');
              }
              solenoidState[1].currentState = parseInt(settings.solenoid1_manual) || 0;
            }
            
            // Load manual state untuk solenoid 2
            if (settings.solenoid2_manual !== undefined) {
              const state = parseInt(settings.solenoid2_manual);
              if (state === 1) {
                const elOn = document.getElementById('solenoid2on');
                const lblOn = document.getElementById('label-solenoid2-on');
                const lblOff = document.getElementById('label-solenoid2-off');
                if (elOn) elOn.checked = true;
                if (lblOn) lblOn.classList.add('active');
                if (lblOff) lblOff.classList.remove('active');
              }
              solenoidState[2].currentState = parseInt(settings.solenoid2_manual) || 0;
            }
            
            // Load auto settings untuk solenoid 1
            if (settings.solenoid1_auto) {
              const autoSettings = settings.solenoid1_auto;
              for (let nodeNum = 1; nodeNum <= 4; nodeNum++) {
                if (autoSettings[`waterlvA${nodeNum}`] !== undefined) {
                  const inputA = document.getElementById(`threshold-node${nodeNum}-waterlvA-solenoid1`);
                  if (inputA) inputA.value = autoSettings[`waterlvA${nodeNum}`];
                }
                if (autoSettings[`waterlvB${nodeNum}`] !== undefined) {
                  const inputB = document.getElementById(`threshold-node${nodeNum}-waterlvB-solenoid1`);
                  if (inputB) inputB.value = autoSettings[`waterlvB${nodeNum}`];
                }
              }
              if (autoSettings.action) {
                const actionSelect = document.getElementById('action-solenoid1');
                if (actionSelect) actionSelect.value = autoSettings.action;
              }
            }
            
            // Load auto settings untuk solenoid 2
            if (settings.solenoid2_auto) {
              const autoSettings = settings.solenoid2_auto;
              for (let nodeNum = 1; nodeNum <= 4; nodeNum++) {
                if (autoSettings[`waterlvA${nodeNum}`] !== undefined) {
                  const inputA = document.getElementById(`threshold-node${nodeNum}-waterlvA-solenoid2`);
                  if (inputA) inputA.value = autoSettings[`waterlvA${nodeNum}`];
                }
                if (autoSettings[`waterlvB${nodeNum}`] !== undefined) {
                  const inputB = document.getElementById(`threshold-node${nodeNum}-waterlvB-solenoid2`);
                  if (inputB) inputB.value = autoSettings[`waterlvB${nodeNum}`];
                }
              }
              if (autoSettings.action) {
                const actionSelect = document.getElementById('action-solenoid2');
                if (actionSelect) actionSelect.value = autoSettings.action;
              }
            }
          }
        })
        .catch(error => {
          console.error('Error loading settings:', error);
        });
    }

    // Load settings saat halaman dimuat
    window.addEventListener('DOMContentLoaded', loadSavedSettings);

    // Fungsi untuk check dan execute automatic control
    function checkAutoControl(solenoidNum) {
      if (solenoidState[solenoidNum].mode !== 'auto') return;
      
      const actionEl = document.getElementById(`action-solenoid${solenoidNum}`);
      if (!actionEl) return;
      
      // Cek semua node (1 sampai 4)
      let allNodesMet = true;
      
      for (let nodeNum = 1; nodeNum <= 4; nodeNum++) {
        const thresholdA = document.getElementById(`threshold-node${nodeNum}-waterlvA-solenoid${solenoidNum}`);
        const thresholdB = document.getElementById(`threshold-node${nodeNum}-waterlvB-solenoid${solenoidNum}`);
        
        if (!thresholdA || !thresholdB) {
          allNodesMet = false;
          break;
        }
        
        const threshA = parseFloat(thresholdA.value);
        const threshB = parseFloat(thresholdB.value);
        
        // Jika threshold tidak diisi, anggap node ini tidak memenuhi
        if (isNaN(threshA) || isNaN(threshB)) {
          allNodesMet = false;
          break;
        }
        
        const currentWaterA = sensorData[nodeNum].waterlvA;
        const currentWaterB = sensorData[nodeNum].waterlvB;
        
        // Node harus memenuhi KEDUA threshold (Water Level A DAN B)
        const nodeConditionMet = (currentWaterA >= threshA) && (currentWaterB >= threshB);
        
        if (!nodeConditionMet) {
          allNodesMet = false;
          break;
        }
      }
      
      // Tentukan state baru berdasarkan apakah semua node memenuhi kondisi
      // Jika action = 'on' => newState = 1 ketika allNodesMet true
      // Jika action = 'off' => newState = 0 ketika allNodesMet true
      const action = actionEl.value;
      const newState = allNodesMet ? (action === 'on' ? 1 : 0) : (action === 'on' ? 0 : 1);
      
      // Hanya publish jika state berubah
      if (newState !== solenoidState[solenoidNum].currentState) {
        solenoidState[solenoidNum].currentState = newState;
        
        const controlData = solenoidNum === 1 
          ? { solenoidSatu: newState }
          : { solenoidDua: newState };
        
        // Konsisten publish pada topik SmIr/kontrol
        client.publish("SmIr/kontrol", JSON.stringify(controlData), { qos: 1, retain: true });
        console.log(`Auto control Solenoid ${solenoidNum}:`, controlData, `(All nodes met: ${allNodesMet})`);
        
        // SIMPAN ke DB sebagai aksi otomatis (type 'auto') untuk tracking/history
        saveToDatabase('auto_action', solenoidNum, controlData);
      }
    }
  </script>
</body>
