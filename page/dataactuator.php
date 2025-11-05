<?php
if (isset($_POST['reset_data'])) {
  mysqli_query($connection, "TRUNCATE TABLE actuator_history");
  echo "<script>alert('Semua data actuator berhasil direset!'); location.href='?page=dataactuator';</script>";
}

$sql = "SELECT * FROM actuator_history ORDER BY created_at DESC LIMIT 1000";
$result = mysqli_query($connection, $sql);
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Data Actuator</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Data Actuator</li>
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
            <div class="card-header position-relative">
              <h3 class="card-title">Actuator Control History</h3>
              <form method="POST" onsubmit="return confirm('Yakin ingin menghapus semua data actuator?')"
                    style="position: absolute; right: 1rem; top: 0.5rem;">
                <button type="submit" name="reset_data" class="btn btn-danger btn-sm">
                  <i class="fas fa-trash-alt"></i> Reset
                </button>
              </form>
            </div>

            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Solenoid</th>
                    <th>Mode</th>
                    <th>Command</th>
                    <th>Details</th>
                    <th>Time</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) { 
                      setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'Indonesian_indonesia.1252');
                      $timestamp = strtotime($row['created_at']);
                      $formattedTime = strftime('%d %B %Y %H:%M:%S', $timestamp);
                      
                      $modeBadge = $row['mode'] === 'manual' 
                        ? '<span class="badge badge-primary">Manual</span>' 
                        : '<span class="badge badge-success">Otomatis</span>';
                      
                      $command = '';
                      if ($row['mode'] === 'manual') {
                        $state = $row['state'];
                        $command = $state == 1 
                          ? '<span class="badge badge-success">ON</span>' 
                          : '<span class="badge badge-danger">OFF</span>';
                      } else {
                        $command = '<span class="badge badge-info">Settings Update</span>';
                      }
                      
                      $details = '';
                      if ($row['mode'] === 'auto' && !empty($row['threshold_data'])) {
                        $thresholdData = json_decode($row['threshold_data'], true);
                        $details = '<small>';
                        $details .= 'Action: <strong>' . strtoupper($row['action']) . '</strong><br>';
                        for ($i = 1; $i <= 4; $i++) {
                          if (isset($thresholdData["waterlvA{$i}"]) && isset($thresholdData["waterlvB{$i}"])) {
                            $details .= "Node {$i}: A={$thresholdData["waterlvA{$i}"]}cm, B={$thresholdData["waterlvB{$i}"]}cm<br>";
                          }
                        }
                        $details .= '</small>';
                      } else if ($row['mode'] === 'manual') {
                        $details = '<small>Direct control command</small>';
                      }
                      ?>
                      <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td>
                          <span class="badge badge-<?php echo $row['solenoid_num'] == 1 ? 'info' : 'warning'; ?>">
                            Solenoid <?php echo $row['solenoid_num']; ?>
                          </span>
                        </td>
                        <td><?php echo $modeBadge; ?></td>
                        <td><?php echo $command; ?></td>
                        <td><?php echo $details; ?></td>
                        <td><?php echo $formattedTime; ?></td>
                      </tr>
                    <?php 
                    }
                  } else { ?>
                    <tr>
                      <td colspan="6" class="text-center">Belum ada history actuator</td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Summary Cards -->
          <div class="row">
            <div class="col-lg-6">
              <div class="card card-primary">
                <div class="card-header">
                  <h3 class="card-title">Solenoid 1 - Last Activity</h3>
                </div>
                <div class="card-body">
                  <?php
                  $sql1 = "SELECT * FROM actuator_history WHERE solenoid_num = 1 ORDER BY created_at DESC LIMIT 1";
                  $result1 = mysqli_query($connection, $sql1);
                  if ($row1 = mysqli_fetch_assoc($result1)) {
                    $timestamp1 = strtotime($row1['created_at']);
                    $time1 = strftime('%d %B %Y %H:%M:%S', $timestamp1);
                    echo "<p><strong>Mode:</strong> " . ucfirst($row1['mode']) . "</p>";
                    if ($row1['mode'] === 'manual') {
                      echo "<p><strong>State:</strong> " . ($row1['state'] == 1 ? 'ON' : 'OFF') . "</p>";
                    }
                    echo "<p><strong>Time:</strong> {$time1}</p>";
                  } else {
                    echo "<p>No activity yet</p>";
                  }
                  ?>
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <div class="card card-warning">
                <div class="card-header">
                  <h3 class="card-title">Solenoid 2 - Last Activity</h3>
                </div>
                <div class="card-body">
                  <?php
                  $sql2 = "SELECT * FROM actuator_history WHERE solenoid_num = 2 ORDER BY created_at DESC LIMIT 1";
                  $result2 = mysqli_query($connection, $sql2);
                  if ($row2 = mysqli_fetch_assoc($result2)) {
                    $timestamp2 = strtotime($row2['created_at']);
                    $time2 = strftime('%d %B %Y %H:%M:%S', $timestamp2);
                    echo "<p><strong>Mode:</strong> " . ucfirst($row2['mode']) . "</p>";
                    if ($row2['mode'] === 'manual') {
                      echo "<p><strong>State:</strong> " . ($row2['state'] == 1 ? 'ON' : 'OFF') . "</p>";
                    }
                    echo "<p><strong>Time:</strong> {$time2}</p>";
                  } else {
                    echo "<p>No activity yet</p>";
                  }
                  ?>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    $("#example1").DataTable({
      "responsive": true,
      "lengthChange": true,
      "autoWidth": false,
      "order": [[0, "desc"]],
      "buttons": ["copy", "csv", "excel", "pdf", "print"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  });
</script>
