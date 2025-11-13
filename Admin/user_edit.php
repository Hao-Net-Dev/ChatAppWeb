<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';

$flash_success = '';
$flash_error = '';

$user = null;
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
	$stmt = $conn->prepare('SELECT UserId, Username, Email, Role FROM Users WHERE UserId = ?');
	if ($stmt) {
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$r = $stmt->get_result();
		$user = $r ? $r->fetch_assoc() : null;
		$stmt->close();
	}
}

if (!$user) {
	http_response_code(404);
	echo 'User not found';
	exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf($_POST['csrf_token'] ?? '')) {
		$flash_error = 'CSRF token không hợp lệ.';
	} else {
		try {
			$username = trim($_POST['username'] ?? '');
			$email = trim($_POST['email'] ?? '');
			$role = ($_POST['role'] ?? 'User') === 'Admin' ? 'Admin' : 'User';
			$newPassword = $_POST['password'] ?? '';

			if ($username === '' || $email === '') {
				throw new Exception('Thiếu thông tin bắt buộc.');
			}
			if ($id === intval($_SESSION['user_id']) && $role !== $_SESSION['role']) {
				throw new Exception('Không thể thay đổi quyền của chính bạn.');
			}

			if ($newPassword !== '') {
				$hash = password_hash($newPassword, PASSWORD_BCRYPT);
				$stmt = $conn->prepare('UPDATE Users SET Username = ?, Email = ?, Role = ?, Password = ? WHERE UserId = ?');
				if (!$stmt) throw new Exception('Lỗi CSDL: ' . $conn->error);
				$stmt->bind_param('ssssi', $username, $email, $role, $hash, $id);
			} else {
				$stmt = $conn->prepare('UPDATE Users SET Username = ?, Email = ?, Role = ? WHERE UserId = ?');
				if (!$stmt) throw new Exception('Lỗi CSDL: ' . $conn->error);
				$stmt->bind_param('sssi', $username, $email, $role, $id);
			}
			$stmt->execute();
			$stmt->close();
			$flash_success = 'Cập nhật người dùng thành công.';
			// refresh current user data
			$user['Username'] = $username;
			$user['Email'] = $email;
			$user['Role'] = $role;
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
	<title>Admin - Sửa người dùng</title>
	<link rel="stylesheet" href="../css/admin.css">
	<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
</head>
<body>
	<?php admin_render_header('users'); ?>

	<main class="admin-container">
		<div class="header-bar">
			<h1 class="admin-title">Sửa người dùng #<?php echo (int)$user['UserId']; ?></h1>
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
							<input type="text" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
						</div>
						<div>
							<label>Email</label>
							<input type="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
						</div>
						<div>
							<label>Mật khẩu mới (để trống nếu không đổi)</label>
							<input type="password" name="password" placeholder="••••••••">
						</div>
						<div>
							<label>Role</label>
							<select name="role">
								<option value="User" <?php echo $user['Role']==='User'?'selected':''; ?>>User</option>
								<option value="Admin" <?php echo $user['Role']==='Admin'?'selected':''; ?>>Admin</option>
							</select>
						</div>
					</div>
					<button class="btn btn-primary" type="submit">Lưu</button>
					<a class="btn" href="./users.php">Quay lại</a>
				</form>
			</div>
		</section>
	</main>
</body>
</html>


