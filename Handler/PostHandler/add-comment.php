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
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
// [QUAN TRỌNG] Lấy ParentCommentId từ AJAX
$parent_id = isset($_POST['parent_id']) && (int)$_POST['parent_id'] > 0 ? (int)$_POST['parent_id'] : NULL;


if ($post_id === 0 || empty($content)) {
    echo json_encode(['status' => 'error', 'message' => 'Nội dung không hợp lệ.']);
    exit();
}

try {
    // 1. Thêm bình luận vào CSDL
    $sql_insert = "INSERT INTO comments (PostId, UserId, Content, ParentCommentId) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iisi", $post_id, $user_id, $content, $parent_id);
    $stmt_insert->execute();
    $new_comment_id = $conn->insert_id; // Lấy ID của bình luận vừa tạo
    $stmt_insert->close();

    // 2. Lấy thông tin user (Avatar, Username) để hiển thị lại
    $sql_user = "SELECT Username, AvatarPath FROM users WHERE UserId = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_info = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();
    
    $conn->close();

    // 3. Trả về dữ liệu bình luận mới (JSON)
    $new_comment = [
        'CommentId' => $new_comment_id,
        'Username' => $user_info['Username'],
        'AvatarPath' => $user_info['AvatarPath'], // Gửi avatar path
        'Content' => $content,
        'ParentCommentId' => $parent_id
    ];
    
    echo json_encode(['status' => 'success', 'comment' => $new_comment]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if ($conn) $conn->close();
}
?>