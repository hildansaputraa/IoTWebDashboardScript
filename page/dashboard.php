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
                      <i class="fas fa-info-circle"></i> Mode otomatis aktif. Solenoid akan dikontrol berdasarkan water level.
                    </div>
                    <div class="form-group">
                      <label>Node yang dipantau:</label>
                      <select class="form-control" id="auto-node-solenoid1">
                        <option value="1">Node 1</option>
                        <option value="2">Node 2</option>
                        <option value="3">Node 3</option>
                        <option value="4">Node 4</option>
                      </select>
                    </div>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Threshold Water Level A (cm):</label>
                          <input type="number" class="form-control" id="threshold-waterlvA-solenoid1" placeholder="Contoh: 50" step="0.1">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Threshold Water Level B (cm):</label>
                          <input type="number" class="form-control" id="threshold-waterlvB-solenoid1" placeholder="Contoh: 30" step="0.1">
                        </div>
                      </div>
                    </div>
                    <div class="form-group">
                      <label>Kondisi:</label>
                      <select class="form-control" id="condition-solenoid1">
                        <option value="both">Kedua level harus memenuhi (AND)</option>
                        <option value="either">Salah satu level memenuhi (OR)</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Aksi saat threshold terpenuhi:</label>
                      <select class="form-control" id="action-solenoid1">
                        <option value="on">Nyalakan Solenoid (ON)</option>
                        <option value="off">Matikan Solenoid (OFF)</option>
                      </select>
                    </div>
                    <small class="text-muted">Solenoid akan ON/OFF ketika water level memenuhi kondisi threshold</small>
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

                  <!-- Manual Control -->
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

                  <!-- Auto Control Settings -->
                  <div id="auto-control-solenoid2" style="display: none;">
                    <div class="alert alert-info">
                      <i class="fas fa-info-circle"></i> Mode otomatis aktif. Solenoid akan dikontrol berdasarkan water level.
                    </div>
                    <div class="form-group">
                      <label>Node yang dipantau:</label>
                      <select class="form-control" id="auto-node-solenoid2">
                        <option value="1">Node 1</option>
                        <option value="2">Node 2</option>
                        <option value="3">Node 3</option>
                        <option value="4">Node 4</option>
                      </select>
                    </div>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Threshold Water Level A (cm):</label>
                          <input type="number" class="form-control" id="threshold-waterlvA-solenoid2" placeholder="Contoh: 50" step="0.1">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Threshold Water Level B (cm):</label>
                          <input type="number" class="form-control" id="threshold-waterlvB-solenoid2" placeholder="Contoh: 30" step="0.1">
                        </div>
                      </div>
                    </div>
                    <div class="form-group">
                      <label>Kondisi:</label>
                      <select class="form-control" id="condition-solenoid2">
                        <option value="both">Kedua level harus memenuhi (AND)</option>
                        <option value="either">Salah satu level memenuhi (OR)</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Aksi saat threshold terpenuhi:</label>
                      <select class="form-control" id="action-solenoid2">
                        <option value="on">Nyalakan Solenoid (ON)</option>
                        <option value="off">Matikan Solenoid (OFF)</option>
                      </select>
                    </div>
                    <small class="text-muted">Solenoid akan ON/OFF ketika water level memenuhi kondisi threshold</small>
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
          checkAutoControl(1);
          checkAutoControl(2);

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
        solenoidSatu: solenoidNum === 1 ? state : solenoidState[2].currentState,
        solenoidDua: solenoidNum === 2 ? state : solenoidState[1].currentState
      };
      
      solenoidState[solenoidNum].currentState = state;
      
      client.publish("SmIr/control", JSON.stringify(controlData), { qos: 1, retain: true });
      console.log("Published control:", controlData);
    }

    // Fungsi untuk check dan execute automatic control
    function checkAutoControl(solenoidNum) {
      if (solenoidState[solenoidNum].mode !== 'auto') return;
      
      const nodeSelect = document.getElementById(`auto-node-solenoid${solenoidNum}`);
      const thresholdA = document.getElementById(`threshold-waterlvA-solenoid${solenoidNum}`);
      const thresholdB = document.getElementById(`threshold-waterlvB-solenoid${solenoidNum}`);
      const condition = document.getElementById(`condition-solenoid${solenoidNum}`);
      const action = document.getElementById(`action-solenoid${solenoidNum}`);
      
      if (!nodeSelect || !thresholdA || !thresholdB || !condition || !action) return;
      
      const selectedNode = parseInt(nodeSelect.value);
      const threshA = parseFloat(thresholdA.value);
      const threshB = parseFloat(thresholdB.value);
      
      if (isNaN(threshA) || isNaN(threshB)) return;
      
      const currentWaterA = sensorData[selectedNode].waterlvA;
      const currentWaterB = sensorData[selectedNode].waterlvB;
      
      let conditionMet = false;
      
      if (condition.value === 'both') {
        // AND: kedua threshold harus terpenuhi
        conditionMet = (currentWaterA >= threshA) && (currentWaterB >= threshB);
      } else {
        // OR: salah satu threshold terpenuhi
        conditionMet = (currentWaterA >= threshA) || (currentWaterB >= threshB);
      }
      
      const newState = conditionMet ? (action.value === 'on' ? 1 : 0) : (action.value === 'on' ? 0 : 1);
      
      // Hanya publish jika state berubah
      if (newState !== solenoidState[solenoidNum].currentState) {
        solenoidState[solenoidNum].currentState = newState;
        
        const controlData = {
          solenoidSatu: solenoidNum === 1 ? newState : solenoidState[2].currentState,
          solenoidDua: solenoidNum === 2 ? newState : solenoidState[1].currentState
        };
        
        client.publish("SmIr/control", JSON.stringify(controlData), { qos: 1, retain: true });
        console.log(`Auto control Solenoid ${solenoidNum}:`, controlData);
      }
    }
  </script>
</body>