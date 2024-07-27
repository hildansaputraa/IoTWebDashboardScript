<?php
$page = $_GET['page'];
$insert = false;

if($_SESSION['role'] != "Admin"){
  echo "<script> location.href='index.php' </script>";
}

if (isset($_POST['edit_data'])) {
    $old_id = $_POST['edit_data'];
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $role = $_POST['role'];
    $active = $_POST['active'];

    if($_POST['password'] == ""){
        $sql_edit = "UPDATE user SET username = '$username', fullname = '$fullname', role = '$role', active = '$active' WHERE username = '$old_id'";
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql_edit = "UPDATE user SET username = '$username', password = '$password', fullname = '$fullname', role = '$role', active = '$active' WHERE username = '$old_id'";
    }

    mysqli_query($connection, $sql_edit);
} else if (isset($_POST['username'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = $_POST['fullname'];
    $role = $_POST['role'];

    $sql_insert = "INSERT INTO user (username, password, fullname, role) VALUES ('$username', '$password', '$fullname', '$role')";
    mysqli_query($connection, $sql_insert);
    $insert = true;
}

if (isset($_GET['edit'])) {
    $edit_username = $_GET['edit'];
    $sql_Select_data = "SELECT * FROM user WHERE username = '$edit_username' LIMIT 1";
    $result = mysqli_query($connection, $sql_Select_data);
    $data = mysqli_fetch_assoc($result);
}

$sql = "SELECT * FROM user";
$result = mysqli_query($connection, $sql);
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">User</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="?page=dashboard">Home</a></li>
                        <li class="breadcrumb-item active">User</li>
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
                            <h3 class="card-title">Registered User</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Fullname</th>
                                        <th>Role</th>
                                        <th>Active Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    while ($row = mysqli_fetch_assoc($result)) { ?>
                                    <tr>
                                        <td><?php echo $row['username'] ?> </td>
                                        <td><?php echo $row['fullname'] ?> </td>
                                        <td><?php echo $row['role'] ?> </td>
                                        <td><?php echo $row['active'] ?></td>
                                        <td><a href="?page=<?php echo $page ?>&edit=<?php echo $row['username'] ?>"><i class="far fa-edit"></i></a></td>
                                    </tr>
                                    <?php } ?>
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
                            <h3 class="card-title">Add User</h3>
                        </div>
                        <!-- /.card-header -->
                        <!-- form start -->
                        <form method="POST" action="?page=<?php echo $page ?>">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="form-group">
                                    <label>Fullname</label>
                                    <input type="text" class="form-control" name="fullname" required>
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <div class="input-group">
                                        <select class="form-control" name="role">
                                            <option value="Admin">Admin</option>
                                            <option value="User">User</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>
                    <?php } else { ?>
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Change Data User</h3>
                        </div>
                        <!-- /.card-header -->
                        <!-- form start -->
                        <form method="POST" action="?page=<?php echo $page ?>">
                            <div class="card-body">
                                <div class="form-group">
                                    <input type="hidden" class="form-control" name="edit_data" value="<?php echo $data['username'] ?>">
                                    <label>Username</label>
                                    <input type="text" class="form-control" name="username" value="<?php echo $data['username'] ?>" placeholder="Username" required>
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" class="form-control" name="password" placeholder="kosongkan jika tidak ingin dirubah" >
                                </div>
                                <div class="form-group">
                                    <label>Fullname</label>
                                    <input type="text" class="form-control" name="fullname" value="<?php echo $data['fullname'] ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <div class="input-group">
                                        <select class="form-control" name="role">
                                            <?php if ($data['role'] == "Admin") { ?>
                                            <option value="Admin">Admin</option>
                                            <option value="User">User</option>
                                            <?php } else { ?>
                                            <option value="User">User</option>
                                            <option value="Admin">Admin</option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="from-group">
                                    <label>Status</label>
                                    <div class="input-group">
                                        <select class="form-control" name="active">
                                            <?php if ($data['active'] == "Yes") { ?>
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
