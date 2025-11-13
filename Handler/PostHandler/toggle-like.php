<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
// Giả sử 'Like' có EmoteId = 1 (bạn có thể thay đổi)
$emote_id = 1; 

if ($post_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Bài đăng không hợp lệ.']);
    exit();
}

try {
    // 1. Kiểm tra xem người dùng đã like bài này chưa
    $sql_check = "SELECT * FROM postemotes WHERE PostId = ? AND UserId = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $post_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    $action = '';

    if ($result_check->num_rows > 0) {
        // 2a. Nếu đã like -> Xóa (unlike)
        $sql_delete = "DELETE FROM postemotes WHERE PostId = ? AND UserId = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $post_id, $user_id);
        $stmt_delete->execute();
        $stmt_delete->close();
        $action = 'unliked';
    } else {
        // 2b. Nếu chưa like -> Thêm (like)
        $sql_insert = "INSERT INTO postemotes (PostId, UserId, EmoteId) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iii", $post_id, $user_id, $emote_id);
        $stmt_insert->execute();
        $stmt_insert->close();
        $action = 'liked';
    }
    $stmt_check->close();

    // 3. Đếm lại tổng số like
    $sql_count = "SELECT COUNT(*) as LikeCount FROM postemotes WHERE PostId = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $post_id);
    $stmt_count->execute();
    $like_count = $stmt_count->get_result()->fetch_assoc()['LikeCount'];
    $stmt_count->close();

    $conn->close();
    
    // 4. Trả về kết quả
    echo json_encode(['status' => 'success', 'action' => $action, 'likeCount' => $like_count]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if ($conn) $conn->close();
}
?>