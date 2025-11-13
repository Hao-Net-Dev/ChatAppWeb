<?php
    $hostname = "localhost";
    $username = "root";
    $password = "";
    $dbname = "chatappsql";

    $conn = new mysqli($hostname, $username, $password, $dbname);

    if ($conn->connect_error) {

        die("Kết nối CSDL thất bại: " . $conn->connect_error);
    }

    // [QUAN TRỌNG] Sửa "utf8" thành "utf8mb4"
    mysqli_set_charset($conn, "utf8mb4"); 
?>