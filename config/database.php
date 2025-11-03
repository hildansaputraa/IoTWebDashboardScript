<?php

$servername = "beige-owl-319179.hostingersite.com";
$username = "smartiot";
$password = "Smartiot123.";
$database = "u212736227_systemiot";

$connection = mysqli_connect($servername,$username,$password,$database);

//periksa koneksi
if(!$connection){
    die("Connection Failed: " . mysqli_connect_error());
}

// echo "Koneksi Berhasil";

?>