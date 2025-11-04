<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <!-- Brand Logo -->
  <a href="?page=dashboard" class="brand-link">
    <img src="../dist/img/unesa.jpg" alt="Logo" class="brand-image" style="float: none; max-height: 50px; width: auto; margin: 0 auto; display: block;">
  </a>
  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="info">
        <a href="#" class="d-block"> <?php echo $_SESSION['fullname'] ?></a>
      </div>
    </div>

    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
          <a href="?page=dashboard" class="nav-link">
            <i class="nav-icon fas fa-chart-line"></i>
            <p>
              Dashboard
            </p>
          </a>
        </li>

        <li class="nav-item">
          <a href="?page=datasensor" class="nav-link">
            <i class="nav-icon fas fa-database"></i>
            <p>
              Data Sensor
            </p>
          </a>
        </li>

        <li class="nav-item">
          <a href="?page=dataactuator" class="nav-link">
            <i class="nav-icon fas fa-database"></i>
            <p>
              Data Actuator
            </p>
          </a>
        </li>

        <li class="nav-item">
          <a href="?page=device" class="nav-link">
            <i class="nav-icon fas fa-laptop-house"></i>
            <p>
              Data Device
            </p>
          </a>
        </li>

        <?php if ($_SESSION['role'] == "Admin") { ?>
          <li class="nav-item">
            <a href="?page=user" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>
                Data User
              </p>
            </a>
          </li>
        <?php } ?>

        <li class="nav-item">
          <a href="logout.php" class="nav-link">
            <i class="nav-icon fas fa-sign-out-alt"></i>
            <p>
              Log Out
            </p>
          </a>
        </li>

      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
</aside>
<!-- /.sidebar -->

<script>
$(document).ready(function() {
  $('.nav-sidebar .nav-link').on('click', function() {
    if ($(window).width() <= 991) {
      setTimeout(function() {
        $('body').removeClass('sidebar-open').addClass('sidebar-collapse');
      }, 100);
    }
  });
});
</script>
