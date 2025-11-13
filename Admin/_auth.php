<?php
session_start();
require_once __DIR__ . '/../Handler/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
	http_response_code(403);
	echo 'Forbidden';
	exit();
}

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function get_csrf_token() {
	return $_SESSION['csrf_token'] ?? '';
}

function validate_csrf($token) {
	return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}
