<?php
$sql = "SELECT * FROM data WHERE sensor_actuator = 'sensor' ORDER BY created_at DESC";
$result = mysqli_query($connection, $sql);
?>

<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Data Sensor</h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Data Sensor</li>
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
        <div class="col-lg-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Sensor Data History</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Id</th>
                    <th>Node</th>
                    <th>Sensor Name</th>
                    <th>Value</th>
                    <th>RSSI (dBm)</th>
                    <th>Topic</th>
                    <th>Time</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  while($row = mysqli_fetch_assoc($result)){ ?>
                    <tr>
                      <td><?php echo htmlspecialchars($row['id']); ?></td>
                      <td><?php echo htmlspecialchars($row['node']); ?></td>
                      <td>
                        <?php 
                          switch($row['name']){
                            case 'tegangan': echo 'Tegangan (V)'; break;
                            case 'arus': echo 'Arus (A)'; break;
                            case 'waterlvA': echo 'Water Level A'; break;
                            case 'waterlvB': echo 'Water Level B'; break;
                            case 'flowrate': echo 'Flow Rate'; break;
                            case 'totalwater': echo 'Total Water'; break;
                            case 'rssi': echo 'RSSI'; break;
                            default: echo htmlspecialchars($row['name']);
                          }
                        ?>
                      </td>
                      <td><?php echo htmlspecialchars($row['value']); ?></td>
                      <td><?php echo htmlspecialchars($row['rssi']); ?></td>
                      <td><?php echo htmlspecialchars($row['mqtt_topic']); ?></td>
                      <td><?php echo htmlspecialchars($row['created_at']); ?></td>
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
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content -->
</div>
