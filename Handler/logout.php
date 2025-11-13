<?php
session_start();

require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    // --- CẬP NHẬT TRẠNG THÁI OFFLINE ---
    $sql = "UPDATE Users SET IsOnline = 0 WHERE UserId = ?";
    
    if ($conn) {
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
        }
    }
}

session_unset();

session_destroy();

if ($conn) $conn->close();
header("Location: ../index.php");
exit(); 
?>