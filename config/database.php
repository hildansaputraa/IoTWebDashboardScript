<?php

$servername = "srv482.hstgr.io";
$username   = "u212736227_smartiot";
$password   = "Smartiot123.";
$database   = "u212736227_systemiot";

// Koneksi ke database
$connection = mysqli_connect($servername, $username, $password, $database);

// Periksa koneksi
if (!$connection) {
    die("Connection Failed: " . mysqli_connect_error());
}

// Set timezone PHP ke Asia/Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');

// Set timezone MySQL untuk sesi ini (tidak butuh SUPER privilege)
mysqli_query($connection, "SET time_zone = '+07:00'");

// Optional: aktifkan charset UTF-8 biar aman untuk teks Indonesia
mysqli_set_charset($connection, "utf8mb4");

// echo "Koneksi Berhasil";
?>
