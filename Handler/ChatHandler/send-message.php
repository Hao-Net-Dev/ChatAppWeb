<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ hoặc chưa đăng nhập.']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if ($receiver_id === 0 || empty($content)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Người nhận hoặc nội dung không hợp lệ.']);
    exit();
}

try {
    $sql = "INSERT INTO Messages (SenderId, ReceiverId, Content) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị CSDL: " . $conn->error);
    }

    $stmt->bind_param("iis", $sender_id, $receiver_id, $content);
    $stmt->execute();

    $new_message_id = $conn->insert_id;
    
    $stmt->close();
    $conn->close();

    echo json_encode(['status' => 'success', 'message_id' => $new_message_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if (isset($stmt) && $stmt) $stmt->close();
    if ($conn) $conn->close();
}
?>