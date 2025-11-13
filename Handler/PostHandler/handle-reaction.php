<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$emote_id = isset($_POST['emote_id']) ? (int)$_POST['emote_id'] : 0; 

if ($post_id === 0 || $emote_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Bài đăng hoặc cảm xúc không hợp lệ.']);
    exit();
}

try {
    // 1. Kiểm tra xem người dùng đã reaction bài này chưa
    $sql_check = "SELECT EmoteId FROM postemotes WHERE PostId = ? AND UserId = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $post_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    $action = '';
    $current_emote = 0;

    if ($result_check->num_rows > 0) {
        $row = $result_check->fetch_assoc();
        $existing_emote_id = $row['EmoteId'];

        if ($existing_emote_id == $emote_id) {
            // 2a. Bấm vào cảm xúc GIỐNG HỆT cái cũ -> Xóa (unlike)
            $sql_delete = "DELETE FROM postemotes WHERE PostId = ? AND UserId = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("ii", $post_id, $user_id);
            $stmt_delete->execute();
            $stmt_delete->close();
            $action = 'unreacted';
        } else {
            // 2b. Bấm vào cảm xúc KHÁC cái cũ -> Cập nhật (change reaction)
            $sql_update = "UPDATE postemotes SET EmoteId = ? WHERE PostId = ? AND UserId = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("iii", $emote_id, $post_id, $user_id);
            $stmt_update->execute();
            $stmt_update->close();
            $action = 'changed';
            $current_emote = $emote_id;
        }
    } else {
        // 2c. Nếu chưa reaction -> Thêm (like)
        $sql_insert = "INSERT INTO postemotes (PostId, UserId, EmoteId) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iii", $post_id, $user_id, $emote_id);
        $stmt_insert->execute();
        $stmt_insert->close();
        $action = 'reacted';
        $current_emote = $emote_id;
    }
    $stmt_check->close();

    // 3. Đếm lại tổng số reactions
    $sql_count = "SELECT COUNT(*) as ReactionCount FROM postemotes WHERE PostId = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $post_id);
    $stmt_count->execute();
    $reaction_count = $stmt_count->get_result()->fetch_assoc()['ReactionCount'];
    $stmt_count->close();
    
    // Lấy 5 cảm xúc hàng đầu
    $sql_top_emotes = "SELECT EmoteId, COUNT(*) as Count FROM postemotes WHERE PostId = ? GROUP BY EmoteId ORDER BY Count DESC LIMIT 5";
    $stmt_top = $conn->prepare($sql_top_emotes);
    $stmt_top->bind_param("i", $post_id);
    $stmt_top->execute();
    $top_emotes_result = $stmt_top->get_result();
    $top_emotes = [];
    while($row = $top_emotes_result->fetch_assoc()) {
        $top_emotes[] = $row['EmoteId'];
    }
    $stmt_top->close();

    $conn->close();
    
    // 4. Trả về kết quả
    echo json_encode([
        'status' => 'success', 
        'action' => $action, 
        'reactionCount' => $reaction_count,
        'currentUserEmote' => $current_emote, // Cảm xúc hiện tại của user (0 nếu unreact)
        'topEmotes' => $top_emotes // Các icon hiển thị
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if ($conn) $conn->close();
}
?>