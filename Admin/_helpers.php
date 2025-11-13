<?php
require_once __DIR__ . '/_auth.php';

function admin_csrf_token() {
	return get_csrf_token();
}

function admin_csrf_field() {
	echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_csrf_token()) . '">';
}

function admin_render_flash($flash_success, $flash_error) {
	if (!empty($flash_success)) {
		echo '<div class="flash flash-success">' . htmlspecialchars($flash_success) . '</div>';
	}
	if (!empty($flash_error)) {
		echo '<div class="flash flash-error">' . htmlspecialchars($flash_error) . '</div>';
	}
}

function admin_render_header($active = '') {
	$username = htmlspecialchars($_SESSION['username'] ?? 'Admin');
	echo '
	<header class="navbar">
		<div class="logo">
			<a href="../index.php">
				<div class="logo-circle"></div>
				<span>ChatApp</span>
			</a>
		</div>
		<nav class="main-nav">
			<a href="../index.php">HOME</a>
			<a href="./index.php">THỐNG KÊ</a>
			<a href="./users.php">USERS</a>
			<a href="./messages.php">MESSAGES</a>
		</nav>
		<div class="auth-buttons">
			<span class="logged-in-user">Admin: ' . $username . '</span>
			<a href="../logout.php" class="btn-text">Logout</a>
		</div>
	</header>';
}

function admin_has_created_at($conn) {
	try {
		$colRes = $conn->query("SHOW COLUMNS FROM Users LIKE 'CreatedAt'");
		return ($colRes && $colRes->num_rows === 1);
	} catch (Exception $e) {
		return false;
	}
}

function admin_get_stats($conn, $hasCreatedAt) {
	$stats = [
		'online' => 0,
		'today' => null,
		'week' => null,
		'month' => null
	];
	try {
		$res = $conn->query('SELECT COUNT(*) AS c FROM Users WHERE IsOnline = 1');
		if ($res) { $row = $res->fetch_assoc(); $stats['online'] = intval($row['c'] ?? 0); }
		if ($hasCreatedAt) {
			$res = $conn->query('SELECT COUNT(*) AS c FROM Users WHERE DATE(CreatedAt) = CURDATE()');
			if ($res) { $row = $res->fetch_assoc(); $stats['today'] = intval($row['c'] ?? 0); }
			$res = $conn->query("SELECT COUNT(*) AS c FROM Users WHERE YEARWEEK(CreatedAt, 1) = YEARWEEK(CURDATE(), 1)");
			if ($res) { $row = $res->fetch_assoc(); $stats['week'] = intval($row['c'] ?? 0); }
			$res = $conn->query("SELECT COUNT(*) AS c FROM Users WHERE YEAR(CreatedAt) = YEAR(CURDATE()) AND MONTH(CreatedAt) = MONTH(CURDATE())");
			if ($res) { $row = $res->fetch_assoc(); $stats['month'] = intval($row['c'] ?? 0); }
		}
	} catch (Exception $e) {
		// ignore
	}
	return $stats;
}

/**
 * Render HTML head section cho admin pages
 */
function admin_render_head($title) {
	echo '<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>' . htmlspecialchars($title) . '</title>
	<link rel="stylesheet" href="../css/admin.css">
	<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
</head>
<body>';
}

/**
 * Tính toán phân trang
 */
function admin_get_pagination($current_page, $total_items, $items_per_page) {
	$current_page = max(1, intval($current_page));
	$total_pages = ceil($total_items / $items_per_page);
	$offset = ($current_page - 1) * $items_per_page;
	
	return [
		'current_page' => $current_page,
		'total_pages' => $total_pages,
		'offset' => $offset,
		'items_per_page' => $items_per_page
	];
}

/**
 * Render phân trang
 */
function admin_render_pagination($current_page, $total_pages, $total_items, $item_label = 'mục') {
	if ($total_pages <= 1) return;
	
	$start_page = max(1, $current_page - 2);
	$end_page = min($total_pages, $current_page + 2);
	
	echo '<div class="pagination">';
	
	// Trang đầu tiên
	if ($start_page > 1) {
		echo '<a href="?page=1" class="pagination-btn">1</a>';
		if ($start_page > 2) {
			echo '<span class="pagination-ellipsis">...</span>';
		}
	}
	
	// Các trang xung quanh
	for ($i = $start_page; $i <= $end_page; $i++) {
		if ($i == $current_page) {
			echo '<span class="pagination-btn active">' . $i . '</span>';
		} else {
			echo '<a href="?page=' . $i . '" class="pagination-btn">' . $i . '</a>';
		}
	}
	
	// Trang cuối cùng
	if ($end_page < $total_pages) {
		if ($end_page < $total_pages - 1) {
			echo '<span class="pagination-ellipsis">...</span>';
		}
		echo '<a href="?page=' . $total_pages . '" class="pagination-btn">' . $total_pages . '</a>';
	}
	
	echo '</div>';
	echo '<div class="pagination-info">';
	echo 'Trang ' . $current_page . ' / ' . $total_pages . ' (' . $total_items . ' ' . $item_label . ')';
	echo '</div>';
}

/**
 * Xử lý POST request với CSRF validation
 */
function admin_handle_post($callback) {
	$flash_success = '';
	$flash_error = '';
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!validate_csrf($_POST['csrf_token'] ?? '')) {
			$flash_error = 'CSRF token không hợp lệ.';
		} else {
			try {
				$result = $callback();
				if (isset($result['success'])) {
					$flash_success = $result['success'];
				}
				if (isset($result['error'])) {
					$flash_error = $result['error'];
				}
			} catch (Exception $ex) {
				$flash_error = $ex->getMessage();
			}
		}
	}
	
	return ['success' => $flash_success, 'error' => $flash_error];
}


