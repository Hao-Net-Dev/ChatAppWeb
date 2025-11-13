<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ.']);
    exit();
}

$blocker_id = $_SESSION['user_id']; // Tôi
$blocked_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0; // Người tôi muốn chặn

if ($blocked_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID người dùng không hợp lệ.']);
    exit();
}

try {
    // Tương tự, INSERT IGNORE
    $sql = "INSERT IGNORE INTO blocked_users (BlockerId, BlockedId) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $blocker_id, $blocked_id);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
    echo json_encode(['status' => 'success', 'message' => 'Đã chặn người này.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>