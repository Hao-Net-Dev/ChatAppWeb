<?php
require_once '../../Handler/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$userId = $_SESSION['user_id'];
// Lấy username hiện tại nếu đã đăng nhập
$current_username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bài đăng mới - ChatApp</title>
    <link rel="stylesheet" href="./../../css/style.css">
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

    <header class="navbar">
    <div class="logo">
        <a href="index.php">
            <div class="logo-circle"></div>
            <span>ChatApp</span>
        </a>
    </div>
    <nav class="main-nav">
        <a href="../../index.php">HOME</a>
        <a href="../../Pages/PostPages/posts.php">POSTS</a>
        <a href="../../Pages/ChatPages/chat.php">CHAT</a>
        <a href="../../Pages/FriendPages/friends.php">FRIENDS</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <a href="../../admin_dashboard.php">ADMIN</a>
        <?php endif; ?>
    </nav>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
            <div class="avatar-menu">
                <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'images/default-avatar.jpg'), '/'); ?>
                <img src="<../../?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="../profile.php">Chỉnh sửa hồ sơ</a>
                    <a href="../../Handler/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="Pages/login.php" class="btn-text">Login</a>
            <a href="Pages/register.php" class="btn-text">Register</a>
        <?php endif; ?>
    </div>
</header>

    <main class="form-page-content">
        <div class="form-container">
            <h2 class="form-title">Tạo bài đăng mới</h2>

            <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<p class="form-error">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
            ?>
            
            <form action="../../Handler/PostHandler/create-post.php" method="POST" enctype="multipart/form-data">
                
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

    <script>
                // Chờ cho toàn bộ trang được tải xong
        document.addEventListener('DOMContentLoaded', function() {
            
            const avatarBtn = document.getElementById('avatarBtn');
            const avatarDropdown = document.getElementById('avatarDropdown');

            // Kiểm tra xem các phần tử này có tồn tại không
            // (vì khách truy cập sẽ không thấy chúng)
            if (avatarBtn && avatarDropdown) {
                
                // 1. Khi nhấp vào avatar
                avatarBtn.addEventListener('click', function(event) {
                    // Ngăn sự kiện click lan ra ngoài
                    event.stopPropagation(); 
                    
                    // Hiển thị hoặc ẩn dropdown
                    avatarDropdown.classList.toggle('open');
                });

                // 2. Khi nhấp ra ngoài (bất cứ đâu trên trang)
                document.addEventListener('click', function(event) {
                    // Nếu dropdown đang mở và cú click không nằm trong dropdown
                    if (avatarDropdown.classList.contains('open') && !avatarDropdown.contains(event.target)) {
                        avatarDropdown.classList.remove('open');
                    }
                });
            }
        });
        </script>
</body>
</html>