<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ.']);
    exit();
}

$hider_id = $_SESSION['user_id']; // Tôi
$hidden_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0; // Người tôi muốn ẩn

if ($hidden_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID người dùng không hợp lệ.']);
    exit();
}

try {
    // INSERT IGNORE sẽ bỏ qua nếu cặp HiderId-HiddenId này đã tồn tại
    $sql = "INSERT IGNORE INTO hidden_feeds (HiderId, HiddenId) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $hider_id, $hidden_id);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
    echo json_encode(['status' => 'success', 'message' => 'Đã ẩn nhật ký người này.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>