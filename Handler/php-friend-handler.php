<?php
require_once('db.php');
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ====================== XỬ LÝ ======================
switch ($action) {

    // 📨 Gửi lời mời kết bạn
    case 'send':
        $friend_id = intval($_POST['friend_id']);
        if ($friend_id == $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Không thể tự gửi lời mời.']);
            exit;
        }

        // Kiểm tra mối quan hệ đã tồn tại chưa
        $check = $conn->prepare("
            SELECT * FROM friends 
            WHERE (UserId=? AND FriendUserId=?) OR (UserId=? AND FriendUserId=?)
        ");
        $check->bind_param('iiii', $user_id, $friend_id, $friend_id, $user_id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            echo json_encode(['status' => 'exists']);
        } else {
            $stmt = $conn->prepare("INSERT INTO friends (UserId, FriendUserId, IsConfirmed) VALUES (?, ?, 0)");
            $stmt->bind_param('ii', $user_id, $friend_id);
            $stmt->execute();
            echo json_encode(['status' => 'sent']);
        }
        break;

    // ✅ Chấp nhận lời mời
    case 'accept':
        $friend_id = intval($_POST['friend_id']);
        $stmt = $conn->prepare("UPDATE friends SET IsConfirmed=1 WHERE UserId=? AND FriendUserId=? LIMIT 1");
        $stmt->bind_param('ii', $friend_id, $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'accepted']);
        break;

    // ❌ Từ chối lời mời
    case 'reject':
        $friend_id = intval($_POST['friend_id']);
        $stmt = $conn->prepare("DELETE FROM friends WHERE UserId=? AND FriendUserId=? LIMIT 1");
        $stmt->bind_param('ii', $friend_id, $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'rejected']);
        break;

    // 🔔 Lấy danh sách lời mời kết bạn
    case 'fetch_requests':
        $stmt = $conn->prepare("
            SELECT f.UserId AS sender_id, u.Username AS sender_name, u.AvatarPath AS sender_avatar
            FROM friends f
            JOIN users u ON f.UserId = u.UserId
            WHERE f.FriendUserId=? AND f.IsConfirmed=0
            ORDER BY f.FriendId DESC
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $requests = [];
        while ($row = $res->fetch_assoc()) {
            $requests[] = $row;
        }
        echo json_encode($requests);
        break;

    // 👬 Lấy danh sách bạn bè đã xác nhận
    case 'fetch_friends':
    $stmt = $conn->prepare("
        SELECT u.UserId, u.Username, u.AvatarPath, u.IsOnline, u.LastSeen
        FROM users u
        WHERE u.UserId IN (
            SELECT FriendUserId FROM friends WHERE UserId=? AND IsConfirmed=1
            UNION
            SELECT UserId FROM friends WHERE FriendUserId=? AND IsConfirmed=1
        )
    ");
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $friends = [];
    while ($row = $res->fetch_assoc()) {
        $friends[] = $row;
    }
    echo json_encode($friends);
    break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ']);
        break;
}
