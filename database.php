<?php
$host = "localhost";
$user = "root";
$password = "ian_db";
$database = "emsdb";

$connection = mysqli_connect($host, $user, $password, $database);

if (mysqli_connect_error()) {
    echo "Error: " . mysqli_error($connection);
}
