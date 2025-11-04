<?php
include "config/database.php";

// Ambil daftar device aktif
$sql = "SELECT * FROM devices WHERE active='Yes'";
$result = mysqli_query($connection, $sql);

// Ambil fallback data terakhir untuk setiap node dan name
$fallback = [];
$fallbackQuery = mysqli_query($connection, "
  SELECT d1.node, d1.name, d1.value, d1.created_at
  FROM data d1
  INNER JOIN (
      SELECT node, name, MAX(created_at) AS last_time
      FROM data
      GROUP BY node, name
  ) d2 ON d1.node = d2.node AND d1.name = d2.name AND d1.created_at = d2.last_time
");
if ($fallbackQuery) {
  while ($row = mysqli_fetch_assoc($fallbackQuery)) {
    $fallback[$row['node']]['data'][$row['name']] = $row['value'];
    $fallback[$row['node']]['time'] = $row['created_at'];
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
              <h1 class="m-0">Monitoring Sensor</h1>
            </div>
          </div>
        </div>
      </div>

      <!-- Monitoring Sensor -->
      <section class="content">
        <div class="container-fluid">
          <div class="row">
            <?php for ($i = 1; $i <= 4; $i++) {
              $nodeData = $fallback[$i]['data'] ?? [];
              $time = isset($fallback[$i]['time']) ? date('H:i:s', strtotime($fallback[$i]['time'])) : '-';
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
                      Data ini terakhir pada: <span id="node<?php echo $i; ?>-time"><?php echo $time; ?></span>
                    </p>
                  </div>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>
      </section>
    </div>
  </div>

  <!-- MQTT JavaScript -->
  <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
  <script>
    const client = mqtt.connect('wss://test.mosquitto.org:8081');

    client.on('connect', () => {
      console.log("MQTT Connected");
      client.subscribe("SmIr/data");
    });

    client.on('message', (topic, message) => {
      const payload = message.toString();
      if (topic === "SmIr/data") {
        try {
          const data = JSON.parse(payload);
          const node = data.Node;
          const fields = ["tegangan", "arus", "waterlvA", "waterlvB", "flowrate", "totalwater", "rssi"];

          fields.forEach(f => {
            const el = document.getElementById(`node${node}-${f}`);
            if (el) el.innerHTML = data[f];
          });

          // Update waktu di bawah RSSI
          const timeEl = document.getElementById(`node${node}-time`);
          if (timeEl) {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
            timeEl.innerHTML = timeStr;
          }
        } catch (err) {
          console.error("Error parsing MQTT data:", err);
        }
      }
    });

    client.on('error', (err) => {
      console.error("MQTT Error:", err);
    });
  </script>
</body>
