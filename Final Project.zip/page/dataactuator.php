<?php
$sql = "SELECT * FROM data WHERE sensor_actuator = 'actuator'";
$result = mysqli_query($connection,$sql);
?>

  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Data Actuator</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="?page=dashboard">Home</a></li>
              <li class="breadcrumb-item active">Data Actuator</li>
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
                <h3 class="card-title">Data Actuator History</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>Id</th>
                      <th>Serial Number</th>
                      <th>Sensor Name</th>
                      <th>Value</th>
                      <th>Topic</th>
                      <th>Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    
                    <?php 
                    while($row = mysqli_fetch_assoc($result)){ ?>
                      <tr>
                      <td><?php echo $row['id'] ?> </td>
                      <td><?php echo $row['serial_number'] ?> </td>
                      <td><?php echo $row['name'] ?> </td>
                      <td><?php echo $row['value']?></td>
                      <td><?php echo $row['mqtt_topic']?></td>
                      <td><?php echo $row['time']?></td>
                    <?php } ?>
                    
                    </tr>
                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col-md-6 -->
          <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>