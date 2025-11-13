<?php
// Bắt đầu session nếu chưa bắt đầu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy username hiện tại nếu đã đăng nhập
$current_username = $_SESSION['username'] ?? 'Guest';
?>
<head>
    <link rel="stylesheet" href="./css/style.css">
</head>

<header class="navbar">
    <div class="logo">
        <a href="index.php">
            <div class="logo-circle"></div>
            <span>ChatApp</span>
        </a>
    </div>
    <nav class="main-nav">
        <a href="index.php">HOME</a>
        <a href="./Pages/PostPages/posts.php">POSTS</a>
        <a href="chat.php">CHAT</a>
        <a href="friends.php">FRIENDS</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <a href="admin_dashboard.php">ADMIN</a>
        <?php endif; ?>
    </nav>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
            <div class="avatar-menu">
                <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'images/default-avatar.jpg'), '/'); ?>
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="profile.php">Chỉnh sửa hồ sơ</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-text">Login</a>
            <a href="register.php" class="btn-text">Register</a>
        <?php endif; ?>
    </div>
</header>

<script>
    (function(){
            const avatarBtn = document.getElementById('avatarBtn');
            const avatarDropdown = document.getElementById('avatarDropdown');
            document.addEventListener('click', (e) => {
                if (avatarBtn && (e.target === avatarBtn || avatarBtn.contains(e.target))) {
                    avatarDropdown.classList.toggle('open');
                } else if (avatarDropdown && !avatarDropdown.contains(e.target)) {
                    avatarDropdown.classList.remove('open');
                }
            });
        })();
</script>
