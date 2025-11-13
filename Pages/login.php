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

    <?php include '../Components/navbar.php' ?>

    <main class="form-page-content">
        <div class="form-container">
            <h2 class="form-title">Login</h2>
            
            <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<p class="form-error">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
            ?>

            <form action="Handler/login.php" method="POST">
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