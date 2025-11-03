<?php
$sql = "SELECT * FROM devices WHERE active='Yes'";
$result = mysqli_query($connection, $sql);
?>

< class="hold-transition sidebar-mini">
  <div class="wrapper">
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Dashboard</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="?page=dashboard">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content-header -->

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
                <!-- /.card-body -->
              </div>
            </div>
          
          <!-- Bagian Sensor Data (Node 1-4) -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card card-success">
                <div class="card-header">
                  <h3 class="card-title">Sensor Data (Node 1-4)</h3>
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
                          <p>Arus: <span id="node1-arus">-</span> A</p>
                          <p>Sensor 1: <span id="node1-sensor1">-</span></p>
                          <p>Sensor 2: <span id="node1-sensor2">-</span></p>
                          <p>Sensor 3: <span id="node1-sensor3">-</span></p>
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
                          <p>Arus: <span id="node2-arus">-</span> A</p>
                          <p>Sensor 1: <span id="node2-sensor1">-</span></p>
                          <p>Sensor 2: <span id="node2-sensor2">-</span></p>
                          <p>Sensor 3: <span id="node2-sensor3">-</span></p>
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
                          <p>Arus: <span id="node3-arus">-</span> A</p>
                          <p>Sensor 1: <span id="node3-sensor1">-</span></p>
                          <p>Sensor 2: <span id="node3-sensor2">-</span></p>
                          <p>Sensor 3: <span id="node3-sensor3">-</span></p>
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
                          <p>Arus: <span id="node4-arus">-</span> A</p>
                          <p>Sensor 1: <span id="node4-sensor1">-</span></p>
                          <p>Sensor 2: <span id="node4-sensor2">-</span></p>
                          <p>Sensor 3: <span id="node4-sensor3">-</span></p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
            <!-- Tambahkan gambar di sini (sebelah kanan Lamp Button) -->
            <div class="col-lg-6">
              <div class="card card-info">
                <div class="card-header">
                  <h3 class="card-title">Maps</h3>
                </div>
                <div class="card-body text-center">
                  <img src="dist\img\maps.jpg" alt="System Diagram" class="img-fluid" style="max-height: 200px;">
                  <p class="mt-2">Node</p>
                </div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-12">
              <div class="card card-lightblue">
                <div class="card-header">
                  <h3 class="card-title">Devices Status</h3>
                </div>
                <!-- /.card-header -->
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
                      <?php
                      while ($row = mysqli_fetch_assoc($result)) { ?> <tr>
                          <td><?php echo $row['serial_number'] ?> </td>
                          <td><?php echo $row['location'] ?> </td>
                          <td style="color:red" id="kelasiottt/status/<?php echo $row['serial_number']?>">offline</td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
                <!-- /.card-body -->
              </div>
              <!-- /.card -->
            </div>
          </div>
        </div>
        <!-- /.container-fluid -->
      </div>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
  </div>
  <!-- /.wrapper -->

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
      document.getElementById("status").innerHTML = "Terhubung";
      document.getElementById("status").style.color = "green";

      client.subscribe("kelasiottt/#", { qos: 1 });
      client.subscribe("SmIr/data", { qos: 1 }); // Subscribe ke topik baru

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

      if(topic.includes("kelasiottt/status/12345678")){
        document.getElementById(topic).innerHTML = payload;

        if(payload.toString()==="offline"){
          document.getElementById(topic).style.color = "red";
        } else if(payload.toString()==="online"){
          document.getElementById(topic).style.color = "green";
        }
      }

      // Tambahkan pemrosesan untuk topik SmIr/data
      if (topic === "SmIr/data") {
        try {
          const data = JSON.parse(payload.toString());
          const node = data.Node;
          
          // Update data untuk node yang sesuai
          document.getElementById(`node${node}-tegangan`).innerHTML = data.tegangan;
          document.getElementById(`node${node}-arus`).innerHTML = data.arus;
          document.getElementById(`node${node}-sensor1`).innerHTML = data.sensor1;
          document.getElementById(`node${node}-sensor2`).innerHTML = data.sensor2;
          document.getElementById(`node${node}-sensor3`).innerHTML = data.sensor3;
          
          console.log(`Data diterima untuk Node ${node}:`, data);
        } catch (error) {
          console.error("Error parsing JSON:", error);
        }
      }
    });

    function publishLamp(value) {
      if (document.getElementById("lampu1nyala").checked) {
        data = "nyala";
      }
      if (document.getElementById("lampu1mati").checked) {
        data = "mati";
      }
      client.publish("kelasiottt/12345678/lampu", data, {
        qos: 1,
        retain: true
      });
    }
  </script>