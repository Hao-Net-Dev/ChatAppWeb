<?php
session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bài đăng mới - ChatApp</title>
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    <style>
        /* Tùy chỉnh textarea cho đẹp hơn */
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background-color: #333333;
            border: 1px solid #555555;
            border-radius: 5px;
            color: #f0f0f0;
            font-family: 'Roboto Mono', monospace;
            font-size: 1em;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            resize: vertical; /* Cho phép thay đổi kích thước theo chiều dọc */
            min-height: 120px;
        }
        .form-group textarea:focus {
            border-color: #ff6666;
            box-shadow: 0 0 5px rgba(255, 102, 102, 0.3);
            outline: none;
        }
        /* Input file */
        .form-group input[type="file"] {
            color: #f0f0f0;
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <main class="form-page-content">
        <div class="form-container">
            <h2 class="form-title">Tạo bài đăng mới</h2>

            <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<p class="form-error">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
            ?>
            
            <form action="Handler/Post/php-create-post.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="post-content">Bạn đang nghĩ gì?</label>
                    <textarea id="post-content" name="content" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="post-image">Chọn ảnh (Tùy chọn)</label>
                    <input type="file" id="post-image" name="image" accept="image/png, image/jpeg, image/gif">
                </div>
                
                <button type="submit" class="btn-submit">Đăng</button>
            </form>
        </div>
    </main>

</body>
</html>