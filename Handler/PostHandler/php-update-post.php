<?php
session_start();
require_once '../db.php';

// (1) Kiểm tra đăng nhập và phương thức POST
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    $_SESSION['error_message'] = "Truy cập không hợp lệ.";
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$content = $_POST['content'];

if ($post_id === 0) {
    $_SESSION['error_message'] = "Bài đăng không hợp lệ.";
    header("Location: ../posts.php");
    exit();
}

try {
    // (2) Kiểm tra quyền sở hữu và lấy đường dẫn ảnh cũ
    $sql_get = "SELECT ImagePath FROM posts WHERE PostId = ? AND UserId = ?";
    $stmt_get = $conn->prepare($sql_get);
    $stmt_get->bind_param("ii", $post_id, $user_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();

    if ($result->num_rows != 1) {
        $_SESSION['error_message'] = "Bạn không có quyền sửa bài đăng này.";
        header("Location: ../posts.php");
        exit();
    }
    
    $post = $result->fetch_assoc();
    $old_image_path = $post['ImagePath'];
    $new_image_path = $old_image_path; // Mặc định giữ ảnh cũ
    $stmt_get->close();


    // (3) Xử lý file upload MỚI (nếu có)
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        
        $target_dir = "../uploads/posts/"; 
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $safe_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array(strtolower($file_extension), $safe_extensions)) {
            $target_file_name = uniqid('post_') . '_' . time() . '.' . $file_extension;
            $target_file_path = $target_dir . $target_file_name;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file_path)) {
                // Upload thành công, gán đường dẫn mới
                $new_image_path = "uploads/posts/" . $target_file_name;
                
                // (4) Xóa ảnh cũ (nếu có)
                if (!empty($old_image_path)) {
                    $old_image_server_path = "../" . $old_image_path;
                    if (file_exists($old_image_server_path)) {
                        unlink($old_image_server_path);
                    }
                }
            } else {
                $_SESSION['error_message'] = "Lỗi khi upload ảnh mới.";
                header("Location: ../edit_post.php?id=" . $post_id);
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Định dạng ảnh không hợp lệ.";
            header("Location: ../edit_post.php?id=" . $post_id);
            exit();
        }
    }

    // (5) Cập nhật CSDL với nội dung và đường dẫn ảnh mới
    $sql_update = "UPDATE posts SET Content = ?, ImagePath = ? WHERE PostId = ? AND UserId = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssii", $content, $new_image_path, $post_id, $user_id);
    $stmt_update->execute();
    
    $stmt_update->close();
    $conn->close();
    
    // (6) Quay về trang posts
    header("Location: ../posts.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    if ($conn) $conn->close();
    header("Location: ../edit_post.php?id=" . $post_id);
    exit();
}
?>