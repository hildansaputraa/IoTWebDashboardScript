<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Favicon / Logo di Tab Browser -->
  <link rel="icon" type="image/jpg" href="../dist/img/unesa.jpg">
  <link rel="shortcut icon" type="image/jpg" href="../dist/img/unesa.jpg">

  <title>IoT Agrowisata Banjarsari</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- Ion Slider -->
  <link rel="stylesheet" href="plugins/ion-rangeslider/css/ion.rangeSlider.min.css">
  <!-- bootstrap slider -->
  <link rel="stylesheet" href="plugins/bootstrap-slider/css/bootstrap-slider.min.css">

  <!-- Script untuk judul tab berjalan -->
  <script>
    // Fungsi untuk membuat judul berjalan di tab browser
    window.addEventListener('load', function() {
      const originalTitle = "IoT Agrowisata Banjarsari - Monitoring & Control System";
      let titleIndex = 0;

      function scrollTitle() {
        // Buat efek scrolling dengan memotong dan menggabungkan string
        document.title = originalTitle.substring(titleIndex) + " | " + originalTitle.substring(0, titleIndex);
        
        titleIndex++;
        if (titleIndex > originalTitle.length) {
          titleIndex = 0;
        }
      }

      // Jalankan animasi setiap 300ms (ubah angka untuk mengatur kecepatan)
      setInterval(scrollTitle, 300);
    });
  </script>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">