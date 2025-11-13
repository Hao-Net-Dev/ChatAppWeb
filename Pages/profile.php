<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit();
}

$userId = intval($_SESSION['user_id']);
$flash_success = '';
$flash_error = '';

// Load current user
$user = null;
if ($conn) {
	$stmt = $conn->prepare('SELECT UserId, Username, Email, FullName, AvatarPath, PhoneNumber, Address, DateOfBirth, Gender FROM Users WHERE UserId = ?');
	if ($stmt) {
		$stmt->bind_param('i', $userId);
		$stmt->execute();
		$res = $stmt->get_result();
		$user = $res ? $res->fetch_assoc() : null;
		$stmt->close();
	}
}

if (!$user) {
	http_response_code(404);
	echo 'User not found';
	exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		$newUsername = trim($_POST['username'] ?? '');
		$newEmail = trim($_POST['email'] ?? '');
		$newFullName = trim($_POST['full_name'] ?? '');
		$newPhoneNumber = trim($_POST['phone_number'] ?? '');
		$newAddress = trim($_POST['address'] ?? '');
		$newDateOfBirth = trim($_POST['date_of_birth'] ?? '');
		$newGender = trim($_POST['gender'] ?? '');

		if ($newUsername === '' || $newEmail === '') {
			throw new Exception('Username và Email là bắt buộc.');
		}

		// Ensure uniqueness for username/email (excluding current user)
		$check = $conn->prepare('SELECT 1 FROM Users WHERE (Username = ? OR Email = ?) AND UserId <> ?');
		if (!$check) throw new Exception('Lỗi CSDL: ' . $conn->error);
		$check->bind_param('ssi', $newUsername, $newEmail, $userId);
		$check->execute();
		$check->store_result();
		if ($check->num_rows > 0) {
			$check->close();
			throw new Exception('Username hoặc Email đã được sử dụng.');
		}
		$check->close();

		$avatarPathToSave = $user['AvatarPath'];

		// Handle avatar upload if provided
		if (isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
			$file = $_FILES['avatar'];
			if ($file['error'] === UPLOAD_ERR_OK) {
				$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mime = finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);
				if (!isset($allowed[$mime])) {
					throw new Exception('Ảnh đại diện phải là JPG, PNG, GIF hoặc WEBP.');
				}
				if ($file['size'] > 3 * 1024 * 1024) {
					throw new Exception('Kích thước ảnh tối đa 3MB.');
				}
				$ext = $allowed[$mime];
				// Tạo thư mục theo user: uploads/avatars/u_{userId}
				$baseDir = __DIR__ . '/uploads/avatars';
				$userDir = $baseDir . '/u_' . $userId;
				if (!is_dir($userDir)) {
					if (!is_dir($baseDir)) {
						mkdir($baseDir, 0755, true);
					}
					mkdir($userDir, 0755, true);
				}
				// Tìm số thứ tự kế tiếp theo dạng avatar_01, avatar_02, ...
				$files = glob($userDir . '/avatar_*.*');
				$maxIdx = 0;
				if ($files !== false) {
					foreach ($files as $f) {
						$bn = basename($f);
						if (preg_match('/^avatar_(\d{2})\.[a-zA-Z0-9]+$/', $bn, $m)) {
							$idx = intval($m[1], 10);
							if ($idx > $maxIdx) $maxIdx = $idx;
						}
					}
				}
				$nextIdx = $maxIdx + 1;
				if ($nextIdx > 99) { $nextIdx = 99; } // giới hạn 2 chữ số
				$filename = 'avatar_' . str_pad((string)$nextIdx, 2, '0', STR_PAD_LEFT) . '.' . $ext;
				$targetPath = $userDir . '/' . $filename;
				if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
					throw new Exception('Không thể lưu ảnh đại diện.');
				}
				$avatarPathToSave = 'uploads/avatars/u_' . $userId . '/' . $filename;
			} else {
				throw new Exception('Tải ảnh thất bại (mã lỗi ' . $file['error'] . ').');
			}
		}

		// Convert empty string to NULL for optional fields
		$newPhoneNumber = $newPhoneNumber === '' ? null : $newPhoneNumber;
		$newAddress = $newAddress === '' ? null : $newAddress;
		$newDateOfBirth = $newDateOfBirth === '' ? null : $newDateOfBirth;
		$newGender = $newGender === '' ? null : $newGender;

		$stmt = $conn->prepare('UPDATE Users SET Username = ?, Email = ?, FullName = ?, AvatarPath = ?, PhoneNumber = ?, Address = ?, DateOfBirth = ?, Gender = ? WHERE UserId = ?');
		if (!$stmt) throw new Exception('Lỗi CSDL: ' . $conn->error);
		$stmt->bind_param('ssssssssi', $newUsername, $newEmail, $newFullName, $avatarPathToSave, $newPhoneNumber, $newAddress, $newDateOfBirth, $newGender, $userId);
		$stmt->execute();
		$stmt->close();

		$_SESSION['username'] = $newUsername;
		$_SESSION['avatar'] = $avatarPathToSave;

		// refresh $user
		$user['Username'] = $newUsername;
		$user['Email'] = $newEmail;
		$user['FullName'] = $newFullName;
		$user['AvatarPath'] = $avatarPathToSave;
		$user['PhoneNumber'] = $newPhoneNumber;
		$user['Address'] = $newAddress;
		$user['DateOfBirth'] = $newDateOfBirth;
		$user['Gender'] = $newGender;

		$flash_success = 'Cập nhật hồ sơ thành công.';
	} catch (Exception $e) {
		$flash_error = $e->getMessage();
	}
}

$defaultAvatar = 'images/default-avatar.jpg';
$avatar = $_SESSION['avatar'] ?? ($user['AvatarPath'] ?: $defaultAvatar);
$avatar = ltrim($avatar, '/');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Hồ sơ cá nhân</title>
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
			<a href="index.php">HOME</a>
			<a href="post.php">POST</a>
			<a href="chat.php">CHAT</a>
			<a href="friends.php">FRIENDS</a>
		</nav>
		<div class="auth-buttons">
			<span class="logged-in-user">Hellu, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
			<div class="avatar-menu">
				<img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
				<div class="avatar-dropdown" id="avatarDropdown">
					<a href="profile.php">Chỉnh sửa hồ sơ</a>
					<a href="logout.php">Logout</a>
				</div>
			</div>
		</div>
	</header>

	<main class="form-page-content">
		<div class="form-container" style="max-width:640px;">
			<h2 class="form-title">Hồ sơ cá nhân</h2>

			<?php if (!empty($flash_success)): ?>
				<p class="form-error" style="color:#10b981;border-color:#10b981;background-color:rgba(16,185,129,0.1);"><?php echo htmlspecialchars($flash_success); ?></p>
			<?php endif; ?>
			<?php if (!empty($flash_error)): ?>
				<p class="form-error"><?php echo htmlspecialchars($flash_error); ?></p>
			<?php endif; ?>

			<form method="post" enctype="multipart/form-data">
				<div class="form-group">
					<label>Ảnh đại diện hiện tại</label>
					<div style="display:flex;align-items:center;gap:12px;">
						<img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:1px solid #444;">
						<input type="file" name="avatar" accept="image/*">
					</div>
				</div>
				<div class="form-group">
					<label>Username</label>
					<input type="text" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
				</div>
				<div class="form-group">
					<label>Email</label>
					<input type="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
				</div>
				<div class="form-group">
					<label>Họ và tên</label>
					<input type="text" name="full_name" value="<?php echo htmlspecialchars($user['FullName'] ?? ''); ?>">
				</div>
				<div class="form-group">
					<label>Số điện thoại</label>
					<input type="tel" name="phone_number" value="<?php echo htmlspecialchars($user['PhoneNumber'] ?? ''); ?>" placeholder="VD: 0123456789">
				</div>
				<div class="form-group">
					<label>Địa chỉ</label>
					<input type="text" name="address" value="<?php echo htmlspecialchars($user['Address'] ?? ''); ?>" placeholder="VD: 123 Đường ABC, Quận XYZ">
				</div>
				<div class="form-group">
					<label>Ngày sinh</label>
					<input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($user['DateOfBirth'] ?? ''); ?>">
				</div>
				<div class="form-group">
					<label>Giới tính</label>
					<div style="display: flex; gap: 20px; align-items: center; margin-top: 8px;">
						<label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: normal;">
							<input type="radio" name="gender" value="Nam" <?php echo (isset($user['Gender']) && $user['Gender'] === 'Nam') ? 'checked' : ''; ?>>
							<span>Nam</span>
						</label>
						<label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: normal;">
							<input type="radio" name="gender" value="Nữ" <?php echo (isset($user['Gender']) && $user['Gender'] === 'Nữ') ? 'checked' : ''; ?>>
							<span>Nữ</span>
						</label>
						<label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: normal;">
							<input type="radio" name="gender" value="Khác" <?php echo (isset($user['Gender']) && $user['Gender'] === 'Khác') ? 'checked' : ''; ?>>
							<span>Khác</span>
						</label>
					</div>
				</div>
				<button type="submit" class="btn-submit">Lưu</button>
			</form>
		</div>
	</main>

	<script>
		const avatarBtn = document.getElementById('avatarBtn');
		const avatarDropdown = document.getElementById('avatarDropdown');
		document.addEventListener('click', (e) => {
			if (avatarBtn && (e.target === avatarBtn || avatarBtn.contains(e.target))) {
				avatarDropdown.classList.toggle('open');
			} else if (avatarDropdown && !avatarDropdown.contains(e.target)) {
				avatarDropdown.classList.remove('open');
			}
		});
	</script>
</body>
</html>


