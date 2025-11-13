<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ChatApp</title>
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
            <?php endif; ?>
        </nav>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn-text">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-text">Login</a>
                <a href="register.php" class="btn-text">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="form-page-content">
        <?php
        if (isset($_SESSION['success_message'])):
        ?>

            <div class="form-container">
                <h2 class="form-title" style="color: #66ccff;">Success!</h2>
                <p style="text-align: center; color: white;"><?php echo $_SESSION['success_message']; ?></p>
                <p style="text-align: center; color: #aaa;">You will be redirected to the Login page in 5 seconds...</p>
            </div>
            
            <script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 5000); // 5000 milliseconds = 5 giây
            </script>

        <?php
            unset($_SESSION['success_message']);
        else:
        ?>
        
            <div class="form-container">
                <h2 class="form-title">Register</h2>
                
                <?php
                    if (isset($_SESSION['error_message'])) {
                        echo '<p class="form-error">' . $_SESSION['error_message'] . '</p>';
                        unset($_SESSION['error_message']);
                    }
                ?>

                <form action="Handler/php-register.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="admin_code">Admin Code (Optional):</label>
                        <input type="text" id="admin_code" name="admin_code">
                    </div>
                    <button type="submit" class="btn-submit">Register</button>
                </form>
            </div>

        <?php
        endif;
        ?>
        
    </main>

</body>
</html>