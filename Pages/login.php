<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./css/style.css">
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php">HOME</a>
                <a href="posts.php">POSTS</a>
                <a href="friend_requests.php">FRIEND REQUESTS</a>
                <a href="friends.php">FRIENDS</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'): ?>
                    <a href="Admin/index.php">ADMIN DASHBOARD</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="logged-in-user">Hellu, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
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

    <main class="form-page-content">
        <div class="form-container">
            <h2 class="form-title">Login</h2>
            
            <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<p class="form-error">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
            ?>

            <form action="Handler/php-login.php" method="POST">
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