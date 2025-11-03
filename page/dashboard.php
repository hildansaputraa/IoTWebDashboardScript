<?php
$sql = "SELECT * FROM devices WHERE active='Yes'";
$result = mysqli_query($connection, $sql);
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
          <div class="row">
            <div class="col-lg-4">
              <div class="small-box bg-warning">
                <div class="inner">
                  <h3 id="potentiometer">-</h3>
                  <p>Potentiometer</p>
                </div>
                <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="small-box bg-danger">
                <div class="inner">
                  <h3 id="temperature">-</h3>
                  <p>Temperature</p>
                </div>
                <div class="icon"><i class="fas fa-temperature-high"></i></div>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="small-box bg-gray">
                <div class="inner">
                  <h3><span id="humidity">-</span>%</h3>
                  <p>Humidity</p>
                </div>
                <div class="icon"><i class="fas fa-water"></i></div>
              </div>
            </div>

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
          </div>

          <!-- ===================== NODE DATA SECTION ===================== -->
          <div class="row" id="node-container">
            <!-- Data Node 1–4 dari topic SmIr/data muncul otomatis -->
          </div>
          <!-- ============================================================= -->

          <div class="row mt-4">
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
                          <td style="color:red" id="kelasiottt/status/<?php echo $row['serial_number'] ?>">offline</td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!-- /.card -->
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

    console.log("Menghubungkan Ke Broker");
    const client = mqtt.connect(host, options);

    client.on("connect", () => {
      console.log("Berhasil Connect");
      client.subscribe("kelasiottt/#", { qos: 1 });
      client.subscribe("SmIr/data", { qos: 1 }); // tambahan untuk Node
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
        if (payload.toString() === "offline") {
          document.getElementById(topic).style.color = "red";
        } else if (payload.toString() === "online") {
          document.getElementById(topic).style.color = "green";
        }
      }

      // Tambahan untuk menerima data Node dari topic SmIr/data
      if (topic === "SmIr/data") {
        try {
          const data = JSON.parse(payload.toString());
          const node = data.Node;

          if (node >= 1 && node <= 4) {
            let card = document.getElementById(`node-${node}`);
            if (!card) {
              const container = document.getElementById("node-container");
              const div = document.createElement("div");
              div.className = "col-lg-3";
              div.innerHTML = `
                <div class="card card-lightblue" id="node-${node}">
                  <div class="card-header">
                    <h3 class="card-title">Node ${node}</h3>
                  </div>
                  <div class="card-body">
                    <p><b>Tegangan:</b> <span id="tegangan-${node}">0</span> V</p>
                    <p><b>Arus:</b> <span id="arus-${node}">0</span> A</p>
                    <p><b>Sensor1:</b> <span id="sensor1-${node}">0</span></p>
                    <p><b>Sensor2:</b> <span id="sensor2-${node}">0</span></p>
                    <p><b>Sensor3:</b> <span id="sensor3-${node}">0</span></p>
                  </div>
                </div>`;
              container.appendChild(div);
            }

            document.getElementById(`tegangan-${node}`).innerText = data.tegangan;
            document.getElementById(`arus-${node}`).innerText = data.arus;
            document.getElementById(`sensor1-${node}`).innerText = data.sensor1;
            document.getElementById(`sensor2-${node}`).innerText = data.sensor2;
            document.getElementById(`sensor3-${node}`).innerText = data.sensor3;
          }
        } catch (e) {
          console.error("Error parsing JSON dari SmIr/data:", e);
        }
      }
    });

    function publishLamp(value) {
      let data;
      if (document.getElementById("lampu1nyala").checked) data = "nyala";
      if (document.getElementById("lampu1mati").checked) data = "mati";
      client.publish("kelasiottt/12345678/lampu", data, { qos: 1, retain: true });
    }
  </script>
</body>
