<?php

$servername = "localhost";
$username = "root";
$password = "";
$database = "systemiot";

$connection = mysqli_connect($servername,$username,$password,$database);

//periksa koneksi
if(!$connection){
    die("Connection Failed: " . mysqli_connect_error());
}

// echo "Koneksi Berhasil";

?>