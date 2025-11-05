<?php
if (isset($_POST['reset_data'])) {
  mysqli_query($connection, "DELETE FROM data WHERE sensor_actuator = 'sensor'");
  echo "<script>alert('Semua data sensor berhasil direset!'); location.href='?page=datasensor';</script>";
}

$sql = "SELECT * FROM data WHERE sensor_actuator = 'sensor' ORDER BY created_at DESC";
$result = mysqli_query($connection, $sql);
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Data Sensor</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Data Sensor</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title">Sensor Data History</h3>
              <form method="POST" onsubmit="return confirm('Yakin ingin menghapus semua data sensor?')">
                <button type="submit" name="reset_data" class="btn btn-danger btn-sm">
                  <i class="fas fa-trash-alt"></i> Reset Data
                </button>
              </form>
            </div>

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
                  <?php while($row = mysqli_fetch_assoc($result)){ ?>
                    <tr>
                      <td><?php echo htmlspecialchars($row['id']); ?></td>
                      <td><?php echo htmlspecialchars($row['node']); ?></td>
                      <td>
                        <?php 
                          switch($row['name']){
                            case 'tegangan': echo 'Tegangan (V)'; break;
                            case 'arus': echo 'Arus (mA)'; break;
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
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
