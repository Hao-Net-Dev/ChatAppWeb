<?php
session_start();
require_once '../db.php';

// (1) Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    $_SESSION['error_message'] = "Truy cập không hợp lệ.";
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$content = $_POST['content'];
$image_path = NULL; // Mặc định là không có ảnh

// (2) Xử lý file upload (nếu có)
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

    $target_dir = "../../uploads/posts/"; 

    // Kiểm tra nếu thư mục tồn tại, không thì tạo mới
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Tạo tên file duy nhất để tránh bị ghi đè
    $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
    $safe_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array(strtolower($file_extension), $safe_extensions)) {
        $target_file_name = uniqid('post_') . '_' . time() . '.' . $file_extension;
        $target_file_path = $target_dir . $target_file_name;

        // Di chuyển file từ thư mục tạm vào thư mục 'uploads/posts/'
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file_path)) {
            // Lưu đường dẫn web-accessible vào CSDL
            // (Giả sử thư mục 'uploads' nằm ngang hàng với 'index.php')
            $image_path = "uploads/posts/" . $target_file_name; 
        } else {
            $_SESSION['error_message'] = "Lỗi khi upload ảnh.";
            header("Location: ../create_post.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Định dạng ảnh không hợp lệ. Chỉ chấp nhận jpg, jpeg, png, gif.";
        header("Location: ../create_post.php");
        exit();
    }
}

// (3) Lưu vào CSDL (bảng `posts`)
try {
    $sql = "INSERT INTO posts (UserId, Content, ImagePath) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Lỗi CSDL: " . $conn->error);
    }

    $stmt->bind_param("iss", $user_id, $content, $image_path);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    // Chuyển hướng về trang "Nhật ký" sau khi đăng thành công
    header("Location: ../../Pages/PostPages/posts.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    if(isset($stmt) && $stmt) $stmt->close();
    if($conn) $conn->close();
    header("Location: ../create_post.php");
    exit();
}
?>