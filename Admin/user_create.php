<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';

$flash_success = '';
$flash_error = '';

// Kiểm tra CreatedAt
$hasCreatedAt = false;
try {
	$colRes = $conn->query("SHOW COLUMNS FROM Users LIKE 'CreatedAt'");
	if ($colRes && $colRes->num_rows === 1) { $hasCreatedAt = true; }
} catch (Exception $e) { $hasCreatedAt = false; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf($_POST['csrf_token'] ?? '')) {
		$flash_error = 'CSRF token không hợp lệ.';
	} else {
		try {
			$username = trim($_POST['username'] ?? '');
			$email = trim($_POST['email'] ?? '');
			$password = $_POST['password'] ?? '';
			$role = ($_POST['role'] ?? 'User') === 'Admin' ? 'Admin' : 'User';
			if ($username === '' || $email === '' || $password === '') {
				throw new Exception('Vui lòng nhập đủ Username, Email, Password.');
			}

			// Kiểm tra trùng email/username
			$emailExists = false;
			$usernameExists = false;

			$checkEmail = $conn->prepare('SELECT 1 FROM Users WHERE Email = ?');
			if (!$checkEmail) throw new Exception('Lỗi CSDL: ' . $conn->error);
			$checkEmail->bind_param('s', $email);
			$checkEmail->execute();
			$checkEmail->store_result();
			$emailExists = $checkEmail->num_rows > 0;
			$checkEmail->close();

			$checkUsername = $conn->prepare('SELECT 1 FROM Users WHERE Username = ?');
			if (!$checkUsername) throw new Exception('Lỗi CSDL: ' . $conn->error);
			$checkUsername->bind_param('s', $username);
			$checkUsername->execute();
			$checkUsername->store_result();
			$usernameExists = $checkUsername->num_rows > 0;
			$checkUsername->close();

			if ($emailExists && $usernameExists) {
				throw new Exception('Username và Email đều đã được sử dụng. Vui lòng chọn thông tin khác.');
			} elseif ($usernameExists) {
				throw new Exception('Username đã được sử dụng. Vui lòng chọn username khác.');
			} elseif ($emailExists) {
				throw new Exception('Email đã được sử dụng. Vui lòng chọn email khác.');
			}

			$hash = password_hash($password, PASSWORD_BCRYPT);
			$sql = $hasCreatedAt
				? 'INSERT INTO Users (Username, Password, Email, Role, CreatedAt) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)'
				: 'INSERT INTO Users (Username, Password, Email, Role) VALUES (?, ?, ?, ?)';
			$stmt = $conn->prepare($sql);
			if (!$stmt) throw new Exception('Lỗi CSDL: ' . $conn->error);
			$stmt->bind_param('ssss', $username, $hash, $email, $role);
			$stmt->execute();
			// Phòng trường hợp race condition trùng email (unique key)
			if ($stmt->errno === 1062) {
				$stmt->close();
				throw new Exception('Username hoặc Email đã tồn tại. Không thể tạo người dùng.');
			}
			$stmt->close();
			$flash_success = 'Tạo người dùng thành công.';
		} catch (Exception $ex) {
			$flash_error = $ex->getMessage();
		}
	}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin - Tạo người dùng</title>
	<link rel="stylesheet" href="../css/admin.css">
	<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
</head>
<body>
	<?php admin_render_header('users'); ?>

	<main class="admin-container">
		<div class="header-bar">
			<h1 class="admin-title">Tạo người dùng</h1>
		</div>

		<?php admin_render_flash($flash_success, $flash_error); ?>

		<section class="section">
			<div class="section-header">
				<h2 class="section-title">Thông tin</h2>
			</div>
			<div class="section-body">
				<form method="post">
					<?php admin_csrf_field(); ?>
					<div class="action-row" style="margin-bottom:10px;">
						<div>
							<label>Username</label>
							<input type="text" name="username" required>
						</div>
						<div>
							<label>Email</label>
							<input type="email" name="email" required>
						</div>
						<div>
							<label>Password</label>
							<input type="password" name="password" required>
						</div>
						<div>
							<label>Role</label>
							<select name="role">
								<option value="User">User</option>
								<option value="Admin">Admin</option>
							</select>
						</div>
					</div>
					<button class="btn btn-primary" type="submit">Tạo</button>
					<a class="btn" href="./users.php">Quay lại</a>
				</form>
			</div>
		</section>
	</main>
</body>
</html>


