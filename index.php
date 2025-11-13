<?php
// Bắt đầu session nếu chưa bắt đầu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy username hiện tại nếu đã đăng nhập
$current_username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatApp Home</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<style>
        /* === HERO SECTION === */
    .hero-section {
        display: flex;
        align-items: center;       /* căn giữa theo chiều dọc */
        justify-content: center;   /* căn giữa theo chiều ngang */
        min-height: calc(100vh - 70px); /* full height trừ navbar (70px giả định) */
        background-color: #F1FAEE;
        text-align: center;
        padding: 20px;
    }

    .hero-section .center-content {
        max-width: 800px;
        color: var(--color-text);
    }

    .hero-section .app-title {
        font-size: 3em;
        font-weight: bold;
        margin-bottom: 10px;
        color: var(--color-accent);
    }

    .hero-section .tagline {
        font-size: 1.5em;
        margin-bottom: 5px;
        color: var(--color-text);
    }

    .hero-section .slogan {
        font-size: 1.2em;
        margin-bottom: 20px;
        color: var(--color-text-muted);
    }

    .hero-section .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .hero-section .btn {
        padding: 12px 25px;
        border-radius: 25px;
        font-weight: bold;
        text-decoration: none;
        transition: background-color 0.2s, color 0.2s;
    }

    .hero-section .btn-primary {
        background-color: var(--color-accent);
        color: var(--color-card);
    }

    .hero-section .btn-primary:hover {
        background-color: var(--color-primary-dark);
    }

    .hero-section .btn-secondary {
        background-color: var(--color-secondary);
        color: var(--color-text);
    }

    .hero-section .btn-secondary:hover {
        background-color: var(--color-bg);
}
</style>
<body>
    <header class="navbar">
    <div class="logo">
        <a href="index.php">
            <div class="logo-circle"></div>
            <span>ChatApp</span>
        </a>
    </div>
    <nav class="main-nav">
        <a href="index.php">HOME</a>
        <a href="Pages/PostPages/posts.php">POSTS</a>
        <a href="Pages/ChatPages/chat.php">CHAT</a>
        <a href="Pages/FriendPages/friends.php">FRIENDS</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <a href="admin_dashboard.php">ADMIN</a>
        <?php endif; ?>
    </nav>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
            <div class="avatar-menu">
                <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'uploads/default-avatar.jpg'), '/'); ?>
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="Pages/profile.php">Chỉnh sửa hồ sơ</a>
                    <a href="Handler/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="Pages/login.php" class="btn-text">Login</a>
            <a href="Pages/register.php" class="btn-text">Register</a>
        <?php endif; ?>
    </div>
</header>

    <main class="hero-section">
        <div class="center-content">
            <h1 class="app-title">CHATAPP</h1>
            <p class="tagline">Connect. Share. Inspire.</p>
            <p class="slogan">WELCOME TO THE FUTURE OF COMMUNICATION</p>
            <div class="action-buttons">
                <?php if (!isset($_SESSION['user_id'])):?>
                    <a href="Pages/login.php" class="btn btn-primary">SIGN IN</a>
                    <a href="Pages/register.php" class="btn btn-secondary">SIGN UP</a>
                <?php endif; ?>
            </div>
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