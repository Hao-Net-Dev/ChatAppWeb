<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// (1) Kiểm tra xem người dùng đã đăng nhập và phương thức là POST chưa
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

if ($post_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Bài đăng không hợp lệ.']);
    exit();
}

try {
    // (2) Lấy ImagePath TRƯỚC KHI XÓA để có thể xóa file
    // Đồng thời kiểm tra xem người này có đúng là chủ bài đăng không
    $sql_get_image = "SELECT ImagePath FROM posts WHERE PostId = ? AND UserId = ?";
    $stmt_get_image = $conn->prepare($sql_get_image);
    $stmt_get_image->bind_param("ii", $post_id, $user_id);
    $stmt_get_image->execute();
    $result = $stmt_get_image->get_result();
    
    if ($result->num_rows == 1) {
        $post = $result->fetch_assoc();
        $image_path = $post['ImagePath'];

        // (3) Xóa bài đăng khỏi CSDL
        // Ghi chú: Các bình luận (comments) và lượt thích (postemotes)
        // sẽ tự động bị xóa theo NẾU bạn đã cài đặt "ON DELETE CASCADE"
        // trong khóa ngoại của CSDL (như trong file .sql gốc của bạn).
        $sql_delete = "DELETE FROM posts WHERE PostId = ? AND UserId = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $post_id, $user_id);
        $stmt_delete->execute();

        // (4) Xóa file ảnh khỏi server (nếu có)
        if (!empty($image_path)) {
            // $image_path_on_server = $_SERVER['DOCUMENT_ROOT'] . '/' . $image_path;
            // Dùng đường dẫn tương đối từ file này
            $image_path_on_server = "../" . $image_path; 
            if (file_exists($image_path_on_server)) {
                unlink($image_path_on_server);
            }
        }
        
        $stmt_delete->close();
        $stmt_get_image->close();
        $conn->close();
        echo json_encode(['status' => 'success']);

    } else {
        // (5) Lỗi: Không phải chủ bài đăng hoặc bài đăng không tồn tại
        $stmt_get_image->close();
        $conn->close();
        echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền xóa bài đăng này.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if ($conn) $conn->close();
}
?>