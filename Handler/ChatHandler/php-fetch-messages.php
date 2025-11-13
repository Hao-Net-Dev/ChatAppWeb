<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Chưa đăng nhập hoặc phương thức không hợp lệ.']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$last_timestamp_ms = isset($_POST['last_timestamp']) ? (float)$_POST['last_timestamp'] : 0;

if ($receiver_id === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Thiếu ID người nhận.']);
    exit();
}

$messages = [];

try {
    
    $sql = "";
    $stmt = null;
    $types = "";
    $params = [];
    
    // Chỉ chọn MessageId, SenderId, Content, SentAt và Username
    $select_cols = "m.MessageId, m.SenderId, m.Content, m.SentAt, u.Username AS SenderName";
    
    // Điều kiện chung cho tin nhắn giữa 2 người
    $where_conversation = "(m.SenderId = ? AND m.ReceiverId = ?) OR (m.SenderId = ? AND m.ReceiverId = ?)";

    if ($last_timestamp_ms == 0) {
        // Tải LẦN ĐẦU: lấy tất cả tin nhắn
        $sql = "SELECT {$select_cols}
                FROM Messages m
                JOIN Users u ON m.SenderId = u.UserId
                WHERE {$where_conversation}
                ORDER BY m.SentAt ASC";
        
        $types = "iiii";
        $params = [$sender_id, $receiver_id, $receiver_id, $sender_id];

    } else {
        // Tải tin nhắn MỚI (Polling)
        $last_timestamp_sql = date('Y-m-d H:i:s', ($last_timestamp_ms / 1000) + 0.001);

        $sql = "SELECT {$select_cols}
                FROM Messages m
                JOIN Users u ON m.SenderId = u.UserId
                WHERE ({$where_conversation})
                AND m.SentAt > ?
                ORDER BY m.SentAt ASC";
        
        $types = "iiiis";
        $params = [$sender_id, $receiver_id, $receiver_id, $sender_id, $last_timestamp_sql];
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            
            // Xử lý để xác định MessageType và FilePath
            $content = $row['Content'] ?? '';
            $row['MessageType'] = 'text';
            $row['FilePath'] = null;

            if (str_starts_with($content, '[IMG]')) {
                $row['MessageType'] = 'image';
                // Lấy đường dẫn ảnh (loại bỏ tiền tố "[IMG]")
                $row['FilePath'] = substr($content, 5); 
                // Thiết lập lại Content là rỗng để frontend không hiển thị chuỗi "[IMG]..."
                $row['Content'] = ''; 
            }
            
            $messages[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();

    echo json_encode($messages);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi máy chủ CSDL: ' . $e->getMessage()]);
    if (isset($stmt) && $stmt) $stmt->close();
    if ($conn) $conn->close();
}
?>