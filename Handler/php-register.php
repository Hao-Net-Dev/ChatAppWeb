<?php
session_start();
require_once 'db.php';

define('SECRET_ADMIN_CODE', 'admin123');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $admin_code = $_POST['admin_code'];

        $role = 'User'; //role default
        if (!empty($admin_code) && $admin_code === SECRET_ADMIN_CODE) {
            $role = 'Admin';
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO Users (Username, Email, Password, Role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception("Lỗi chuẩn bị CSDL: " . $conn->error);
        }

        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        $stmt->execute(); 
        
        $_SESSION['success_message'] = "Account registered successfully!";
        
        $stmt->close();
        $conn->close();
        
        header("Location: ../register.php"); 
        exit();

    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $_SESSION['error_message'] = "Username or Email already exists.";
        } else {
            $_SESSION['error_message'] = "Lỗi CSDL: " . $e->getMessage();
        }

        if (isset($stmt) && $stmt) $stmt->close();
        $conn->close();
        header("Location: ../register.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        
        if (isset($stmt) && $stmt) $stmt->close();
        $conn->close();
        header("Location: ../register.php");
        exit();
    }

} else {
    $conn->close();
    header("Location: ../index.php");
    exit();
}
?>