<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';

// Xử lý POST
$flash = admin_handle_post(function() use ($conn) {
	$action = $_POST['action'] ?? '';
	if ($action === 'delete_user') {
		$userId = intval($_POST['user_id'] ?? 0);
		if ($userId === intval($_SESSION['user_id'])) {
			throw new Exception('Không thể xóa chính bạn.');
		}
		if ($userId <= 0) {
			throw new Exception('ID người dùng không hợp lệ.');
		}
		
		// Xóa các dữ liệu liên quan trước (cascade delete)
		// Xóa friends
		$stmt = $conn->prepare('DELETE FROM friends WHERE UserId = ? OR FriendUserId = ?');
		if ($stmt) {
			$stmt->bind_param('ii', $userId, $userId);
			$stmt->execute();
			$stmt->close();
		}
		
		// Xóa messages
		$stmt = $conn->prepare('DELETE FROM messages WHERE SenderId = ? OR ReceiverId = ?');
		if ($stmt) {
			$stmt->bind_param('ii', $userId, $userId);
			$stmt->execute();
			$stmt->close();
		}
		
		// Xóa comments
		$stmt = $conn->prepare('DELETE FROM comments WHERE UserId = ?');
		if ($stmt) {
			$stmt->bind_param('i', $userId);
			$stmt->execute();
			$stmt->close();
		}
		
		// Xóa posts
		$stmt = $conn->prepare('DELETE FROM posts WHERE UserId = ?');
		if ($stmt) {
			$stmt->bind_param('i', $userId);
			$stmt->execute();
			$stmt->close();
		}
		
		// Xóa user
		$stmt = $conn->prepare('DELETE FROM Users WHERE UserId = ?');
		if (!$stmt) throw new Exception('Lỗi CSDL: ' . $conn->error);
		$stmt->bind_param('i', $userId);
		$stmt->execute();
		$affected = $stmt->affected_rows;
		$stmt->close();
		
		if ($affected > 0) {
			return ['success' => 'Đã xóa người dùng thành công.'];
		} else {
			return ['error' => 'Không tìm thấy người dùng để xóa.'];
		}
	}
	return [];
});

// Phân trang
$items_per_page = 5;
$total_users = 0;
$users = [];
try {
	$count_res = $conn->query('SELECT COUNT(*) as total FROM Users');
	if ($count_res) {
		$total_users = $count_res->fetch_assoc()['total'];
	}
	
	$pagination = admin_get_pagination($_GET['page'] ?? 1, $total_users, $items_per_page);
	$stmt = $conn->prepare('SELECT UserId, Username, Email, Role FROM Users ORDER BY UserId ASC LIMIT ? OFFSET ?');
	if ($stmt) {
		$stmt->bind_param('ii', $pagination['items_per_page'], $pagination['offset']);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) { $users[] = $row; }
		$stmt->close();
	}
} catch (Exception $e) { 
	$flash['error'] = 'Lỗi tải danh sách người dùng.'; 
	$pagination = admin_get_pagination(1, 0, $items_per_page);
}

admin_render_head('Admin - Người dùng');
admin_render_header('users');
?>
	<main class="admin-container">
		<div class="header-bar">
			<h1 class="admin-title">Quản lý người dùng</h1>
			<div class="header-actions">
				<a class="btn btn-outline" href="./user_create.php">Tạo người dùng</a>
			</div>
		</div>

		<?php admin_render_flash($flash['success'], $flash['error']); ?>

		<section class="section">
			<div class="section-header">
				<h2 class="section-title">Danh sách</h2>
			</div>
			<div class="section-body">
				<table>
					<thead>
						<tr>
							<th>ID</th>
							<th>Username</th>
							<th>Email</th>
							<th>Quyền</th>
							<th>Hành động</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($users as $u): ?>
						<tr>
							<td><?php echo (int)$u['UserId']; ?></td>
							<td><?php echo htmlspecialchars($u['Username']); ?></td>
							<td><?php echo htmlspecialchars($u['Email']); ?></td>
							<td>
								<span class="badge <?php echo $u['Role']==='Admin'?'badge-admin':''; ?>">
									<?php echo htmlspecialchars($u['Role']); ?>
								</span>
							</td>
							<td>
								<div class="action-row">
									<a class="btn" href="./user_edit.php?id=<?php echo (int)$u['UserId']; ?>">Sửa</a>
									<form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác!');" style="display: inline;">
										<?php admin_csrf_field(); ?>
										<input type="hidden" name="action" value="delete_user">
										<input type="hidden" name="user_id" value="<?php echo (int)$u['UserId']; ?>">
										<button class="btn btn-danger" type="submit">Xóa</button>
									</form>
								</div>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<?php admin_render_pagination($pagination['current_page'], $pagination['total_pages'], $total_users, 'người dùng'); ?>
			</div>
		</section>
	</main>
</body>
</html>


