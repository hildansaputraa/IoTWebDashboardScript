<?php

include "../config/database.php";

$username = $_GET['username'];

$sql = "SELECT fullname FROM user WHERE username = '$username'";
$result = mysqli_query($connection,$sql);

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)){
    echo $row['fullname'];
    }
} else {
    echo "Data Tidak Ada";
}


?>