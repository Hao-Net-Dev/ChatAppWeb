<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';

// Xử lý POST
$flash = admin_handle_post(function() use ($conn) {
	$action = $_POST['action'] ?? '';
	if ($action === 'delete_message') {
		$messageId = intval($_POST['message_id'] ?? 0);
		$stmt = $conn->prepare('DELETE FROM Messages WHERE MessageId = ?');
		if (!$stmt) throw new Exception('Lỗi CSDL: ' . $conn->error);
		$stmt->bind_param('i', $messageId);
		$stmt->execute();
		$affected = $stmt->affected_rows;
		$stmt->close();
		if ($affected > 0) {
			return ['success' => 'Đã xóa tin nhắn.'];
		} else {
			return ['error' => 'Không tìm thấy tin nhắn.'];
		}
	}
	return [];
});

// Phân trang
$items_per_page = 5;
$total_messages = 0;
$messages = [];
try {
	$count_res = $conn->query('SELECT COUNT(*) as total FROM Messages');
	if ($count_res) {
		$total_messages = $count_res->fetch_assoc()['total'];
	}
	
	$pagination = admin_get_pagination($_GET['page'] ?? 1, $total_messages, $items_per_page);
	$sql = 'SELECT m.MessageId, m.Content, m.SentAt, s.Username AS SenderUsername, r.Username AS ReceiverUsername, m.GroupId
		FROM Messages m
		LEFT JOIN Users s ON m.SenderId = s.UserId
		LEFT JOIN Users r ON m.ReceiverId = r.UserId
		ORDER BY m.MessageId DESC
		LIMIT ? OFFSET ?';
	$stmt = $conn->prepare($sql);
	if ($stmt) {
		$stmt->bind_param('ii', $pagination['items_per_page'], $pagination['offset']);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) { $messages[] = $row; }
		$stmt->close();
	}
} catch (Exception $e) { 
	$flash['error'] = 'Lỗi tải tin nhắn.'; 
	$pagination = admin_get_pagination(1, 0, $items_per_page);
}

admin_render_head('Admin - Tin nhắn');
admin_render_header('messages');
?>
	<main class="admin-container">
		<div class="header-bar">
			<h1 class="admin-title">Tin nhắn gần đây</h1>
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
							<th>Người gửi</th>
							<th>Người nhận</th>
							<th>Nội dung</th>
							<th>Thời gian</th>
							<th>Hành động</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($messages as $m): ?>
						<tr>
							<td><?php echo (int)$m['MessageId']; ?></td>
							<td><?php echo htmlspecialchars($m['SenderUsername'] ?? '—'); ?></td>
							<td>
								<?php
									if (!empty($m['GroupId'])) { echo 'Group #' . (int)$m['GroupId']; }
									else { echo htmlspecialchars($m['ReceiverUsername'] ?? '—'); }
								?>
							</td>
							<td><?php echo htmlspecialchars($m['Content']); ?></td>
							<td><?php echo htmlspecialchars($m['SentAt']); ?></td>
							<td>
								<form method="post" onsubmit="return confirm('Xác nhận xóa tin nhắn?');">
									<?php admin_csrf_field(); ?>
									<input type="hidden" name="action" value="delete_message">
									<input type="hidden" name="message_id" value="<?php echo (int)$m['MessageId']; ?>">
									<button class="btn btn-danger" type="submit">Xóa</button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<?php admin_render_pagination($pagination['current_page'], $pagination['total_pages'], $total_messages, 'tin nhắn'); ?>
			</div>
		</section>
	</main>
</body>
</html>


