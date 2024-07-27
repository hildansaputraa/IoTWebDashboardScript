<?php
$page = $_GET['page'];
$insert = false;

if(isset($_POST['edit_data'])){
  $old_id = $_POST['edit_data'];
  $serial_number = $_POST['serial_number'];
  $mcu_type = $_POST['mcu_type'];
  $location = $_POST['location'];
  $active = $_POST['active'];

  $sql_edit = "UPDATE devices SET serial_number = '$serial_number', mcu_type = '$mcu_type', location = '$location', active = '$active' WHERE serial_number = '$old_id'";
  mysqli_query($connection, $sql_edit);

} else if (isset($_POST['serial_number'])) {
  $serial_number = $_POST['serial_number'];
  $mcu_type = $_POST['mcu_type'];
  $location = $_POST['location'];

  $sql_insert = "INSERT INTO devices (serial_number, mcu_type, location) VALUES ('$serial_number', '$mcu_type', '$location')";
  mysqli_query($connection, $sql_insert);
  $insert = true;
}

if(isset($_GET['edit'])){
  $edit_sn = $_GET['edit'];
  $sql_Select_data = "SELECT * FROM devices WHERE serial_number = '$edit_sn' LIMIT 1";
  $result = mysqli_query($connection,$sql_Select_data);
  $data = mysqli_fetch_assoc($result);
}


$sql = "SELECT * FROM devices";
$result = mysqli_query($connection, $sql);
?>

<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Device</h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Device</li>
          </ol>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <div class="content">
    <div class="container-fluid">
      <?php
      if ($insert == true) {
        alertSuccess('Data Insert Success');
      };
      ?>
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Registered Devices</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Serial Number</th>
                    <th>Microcontroller Type</th>
                    <th>Location</th>
                    <th>Register Time</th>
                    <th>Active Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>

                  <?php
                  while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                      <td><?php echo $row['serial_number'] ?> </td>
                      <td><?php echo $row['mcu_type'] ?> </td>
                      <td><?php echo $row['location'] ?> </td>
                      <td><?php echo $row['created_time'] ?></td>
                      <td><?php echo $row['active'] ?></td>
                      <td><a href="?page=<?php echo $page ?>&edit=<?php echo $row['serial_number'] ?>"><i class="far fa-edit"></i></a></td>
                    <?php } ?>
                    </tr>
                </tbody>
              </table>
            </div>
            <!-- /.card-body -->
          </div>


          <?php
          if (!isset($_GET['edit'])) {
          ?>

            <!-- general form elements -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Add Device</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form method="POST" action="?page=<?php echo $page?>">
                <div class="card-body">
                  <div class="form-group">
                    <label>Serial Number</label>
                    <input type="text" class="form-control" name="serial_number" placeholder="Serial Number or Mac address" required>
                  </div>
                  <div class="form-group">
                    <label>Microcontroller Type</label>
                    <input type="text" class="form-control" name="mcu_type" required>
                  </div>
                  <div class="form-group">
                    <label>Location</label>
                    <input type="text" class="form-control" name="location" required>
                  </div>
                  <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                  </div>
                </div>
              </form>
            </div>
          <?php } else { ?>
            <div class="card card-warning">
              <div class="card-header">
                <h3 class="card-title">Change Data Device</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form method="POST" action="?page=<?php echo $page?>">
                <div class="card-body">
                  <div class="form-group">
                    <input type="hidden" class="form-control" name="edit_data" value="<?php echo $data['serial_number'] ?>" >
                    <label>Serial Number</label>
                    <input type="text" class="form-control" name="serial_number" value="<?php echo $data['serial_number'] ?> " placeholder="Serial Number or Mac address" required>
                  </div>
                  <div class="form-group">
                    <label>Microcontroller Type</label>
                    <input type="text" class="form-control" name="mcu_type" value="<?php echo $data['mcu_type'] ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Location</label>
                    <input type="text" class="form-control" name="location" value="<?php echo $data['location'] ?>" required>
                  </div>
                  <div class="from-group">
                  <label>Status</label>
                    <div class="input-group">
                    <select class="form-control" name="active">
                      <?php if($data['active'] == "Yes"){ ?>
                      <option value="Yes">Active</option>
                      <option value="No">Inactive</option>
                      <?php } else { ?>
                        <option value="No">Inactive</option>
                        <option value="Yes">Active</option>
                        <?php } ?>
                    </select>
                    </div>
                  </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                  </div>
              </form>
            </div>
          <?php } ?>
          <!-- /.card-body -->
        </div>

        <!-- /.col-md-6 -->
        <!-- /.col-md-6 -->
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content -->
</div>