<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';

// Kiểm tra cột CreatedAt
$hasCreatedAt = admin_has_created_at($conn);

// Lấy thống kê
$stats = admin_get_stats($conn, $hasCreatedAt);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin - Thống kê</title>
	<link rel="stylesheet" href="../css/admin.css">
	<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
</head>
<body>
	<?php admin_render_header('stats'); ?>

	<main class="admin-container">
		<div class="header-bar">
			<h1 class="admin-title">Thống kê</h1>
		</div>
		<section class="section">
			<div class="section-header">
				<h2 class="section-title">Tổng quan</h2>
			</div>
			<div class="section-body">
				<table>
					<thead>
						<tr>
							<th>Đang trực tuyến</th>
							<th>Đăng ký hôm nay</th>
							<th>Đăng ký tuần này</th>
							<th>Đăng ký tháng này</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php echo (int)$stats['online']; ?></td>
							<td><?php echo $hasCreatedAt && $stats['today'] !== null ? (int)$stats['today'] : 'N/A'; ?></td>
							<td><?php echo $hasCreatedAt && $stats['week'] !== null ? (int)$stats['week'] : 'N/A'; ?></td>
							<td><?php echo $hasCreatedAt && $stats['month'] !== null ? (int)$stats['month'] : 'N/A'; ?></td>
						</tr>
					</tbody>
				</table>
				<?php if (!$hasCreatedAt): ?>
					<p style="margin-top:8px;color:#bbb;">Gợi ý: thêm cột <code>CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP</code> vào bảng <code>Users</code> để bật thống kê đăng ký.</p>
				<?php endif; ?>
			</div>
		</section>
	</main>
</body>
</html>


