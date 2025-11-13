<?php
require_once '../db.php';
session_start();
if(!isset($_SESSION['user_id'])) exit;

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
$users = [];

if($q !== '') {
    $like = "%$q%";
    $stmt = $conn->prepare("SELECT UserId, Username, AvatarPath FROM users WHERE Username LIKE ? AND UserId != ?");
    $stmt->bind_param('si', $like, $_SESSION['user_id']);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($users);
