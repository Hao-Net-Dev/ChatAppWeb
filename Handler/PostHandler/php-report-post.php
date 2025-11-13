<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ.']);
    exit();
}

$reporter_id = $_SESSION['user_id']; // Tôi
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

if ($post_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID bài đăng không hợp lệ.']);
    exit();
}

try {
    // INSERT IGNORE để tránh một người báo cáo 1 bài nhiều lần
    $sql = "INSERT IGNORE INTO reports (PostId, ReporterId, Status) VALUES (?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $reporter_id);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
    echo json_encode(['status' => 'success', 'message' => 'Đã báo xấu bài đăng.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>