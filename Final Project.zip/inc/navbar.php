<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
    <?php if (!isset($_GET['page']) || $_GET['page'] == "dashboard") { ?>
      <li class="nav-item d-none d-sm-inline-block">
        <a class="nav-link">
          Status Server
          <span class="blink" style='color:red; margin-left: 5px;'>
            <b id="status">
              TERPUTUS
            </b>
          </span>
        </a>
      </li>

      <style type="text/css">
        @-webkit-keyframes blinker {
          from {
            opacity: 1.0;
          }

          to {
            opacity: 0.0;
          }
        }

        .blink {
          text-decoration: blink;
          -webkit-animation-name: blinker;
          -webkit-animation-duration: 0.5s;
          -webkit-animation-iteration-count: infinite;
          -webkit-animation-timing-function: ease-in-out;
          -webkit-animation-direction: alternate;
        }
      </style>


    <?php } ?>

  </ul>
</nav>
<!-- /.navbar -->