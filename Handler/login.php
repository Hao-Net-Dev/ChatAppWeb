<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $sql = "SELECT UserId, Username, Password, Role, AvatarPath FROM Users WHERE Username = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception("Lỗi CSDL: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['Password'])) {
                $_SESSION['user_id'] = $user['UserId'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role'] = $user['Role'];
                $defaultAvatar = 'images/default-avatar.jpg';
                $avatarPath = $user['AvatarPath'] ?? '';
                if (empty($avatarPath)) {
                    $_SESSION['avatar'] = $defaultAvatar;
                } else {
                    $_SESSION['avatar'] = ltrim($avatarPath, '/');
                }

                // --- CẬP NHẬT TRẠNG THÁI ONLINE ---
                $sql_update = "UPDATE Users SET IsOnline = 1 WHERE UserId = ?";
                $stmt_update = $conn->prepare($sql_update);
                if ($stmt_update) {
                    $stmt_update->bind_param("i", $user['UserId']);
                    $stmt_update->execute();
                    $stmt_update->close();
                }
                $stmt->close();
                $conn->close();
                header("Location: ../Pages/ChatPages/chat.php"); // Chuyển hướng đến trang chat
                exit();

            } else {
                throw new Exception("Username or password incorrect.");
            }
        } else {
            throw new Exception("Username or password incorrect.");
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        
        if (isset($stmt) && $stmt) $stmt->close();
        if ($conn) $conn->close();
        header("Location: ../Pages/login.php");
        exit();
    }

} else {
    if ($conn) $conn->close();
    header("Location: ../index.php");
    exit();
}
?>