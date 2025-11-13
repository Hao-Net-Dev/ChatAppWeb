<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
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
        <a href="../index.php">HOME</a>
        <a href="../Pages/PostPages/posts.php">POSTS</a>
        <a href="../Pages/ChatPages/chat.php">CHAT</a>
        <a href="../Pages/FriendPages/friends.php">FRIENDS</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <a href="../../admin_dashboard.php">ADMIN</a>
        <?php endif; ?>
    </nav>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
            <div class="avatar-menu">
                <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'images/default-avatar.jpg'), '/'); ?>
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="../Pages/profile.php">Chỉnh sửa hồ sơ</a>
                    <a href="../Handler/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-text">Login</a>
            <a href="./register.php" class="btn-text">Register</a>
        <?php endif; ?>
    </div>
    </header>

    <main class="form-page-content">
        <div class="form-container">
            <h2 class="form-title">Login</h2>
            
            <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<p class="form-error">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
            ?>

            <form action="../Handler/login.php" method="POST"> 
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-submit">Login</button>
            </form>
        </div>
    </main>

</body>
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
</html>