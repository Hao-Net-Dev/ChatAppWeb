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

if ($receiver_id === 0 || empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID người nhận hoặc không có file ảnh.']);
    exit();
}

$file = $_FILES['image'];

try {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Lỗi upload file (mã lỗi ' . $file['error'] . ').');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        throw new Exception('Chỉ cho phép tải lên file ảnh JPG, PNG, GIF, WEBP.');
    }
    if ($file['size'] > 5 * 1024 * 1024) { // Giới hạn 5MB
        throw new Exception('Kích thước ảnh tối đa 5MB.');
    }

    $ext = $allowed[$mime];
    
    // Tạo thư mục uploads/messages/u_{senderId}
    $baseDir = __DIR__ . '/../uploads/messages';
    $userDir = $baseDir . '/u_' . $sender_id;
    if (!is_dir($userDir)) {
        if (!is_dir($baseDir)) { mkdir($baseDir, 0755, true); }
        mkdir($userDir, 0755, true);
    }

    // Tạo tên file ngẫu nhiên để tránh trùng lặp và đoán tên
    $filename = uniqid('img_') . '.' . $ext;
    $targetPath = $userDir . '/' . $filename;
    $publicPath = 'uploads/messages/u_' . $sender_id . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Không thể lưu file ảnh.');
    }

    // --- LƯU VÀO CSDL VỚI TIỀN TỐ [IMG] TRONG CỘT CONTENT ---
    $image_content = "[IMG]" . $publicPath;
    $sql = "INSERT INTO Messages (SenderId, ReceiverId, Content) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị CSDL: " . $conn->error);
    }

    $stmt->bind_param("iis", $sender_id, $receiver_id, $image_content);
    $stmt->execute();

    $new_message_id = $conn->insert_id;
    
    $stmt->close();
    $conn->close();

    echo json_encode(['status' => 'success', 'message_id' => $new_message_id, 'file_path' => $publicPath]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if (isset($stmt) && $stmt) $stmt->close();
    if ($conn) $conn->close();
}
?>