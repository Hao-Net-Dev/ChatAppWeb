<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$friend_id_to_delete = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($friend_id_to_delete === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID người dùng không hợp lệ.']);
    exit();
}

try {
    // Xóa cả 2 chiều của tình bạn
    $sql = "DELETE FROM friends 
            WHERE (UserId = ? AND FriendUserId = ?) 
               OR (UserId = ? AND FriendUserId = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $current_user_id, $friend_id_to_delete, $friend_id_to_delete, $current_user_id);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
    echo json_encode(['status' => 'success', 'message' => 'Đã hủy kết bạn.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>