<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$sql_current_user = "SELECT AvatarPath FROM users WHERE UserId = ?";
$stmt_current_user = $conn->prepare($sql_current_user);
$stmt_current_user->bind_param("i", $current_user_id);
$stmt_current_user->execute();
$current_user_avatar_result = $stmt_current_user->get_result();
$current_user_avatar = $current_user_avatar_result->num_rows > 0 ? $current_user_avatar_result->fetch_assoc()['AvatarPath'] : '/images/default-avatar.jpg';
$stmt_current_user->close();

// Lấy TẤT CẢ các emotes một lần để dùng
$emotes_map = [];
$emotes_result = $conn->query("SELECT EmoteId, EmoteName, EmoteUnicode FROM emotes");
while ($row = $emotes_result->fetch_assoc()) {
    $emotes_map[$row['EmoteId']] = [
        'name' => $row['EmoteName'],
        'unicode' => $row['EmoteUnicode']
    ];
}

// Hàm PHP đệ quy để render bình luận
function renderComments($post_id, $comments_by_parent, $parent_id = NULL) {
    if (!isset($comments_by_parent[$parent_id])) {
        return; // Không có bình luận con
    }

    $comment_wrapper_class = $parent_id !== NULL ? 'comment-replies' : 'comment-list';
    
    echo "<div class='" . $comment_wrapper_class . "'>";

    foreach ($comments_by_parent[$parent_id] as $comment) {
        $comment_id = $comment['CommentId'];
        ?>
        <div class="comment" id="comment-<?php echo $comment_id; ?>">
            <img src="<?php echo htmlspecialchars($comment['AvatarPath']); ?>" alt="Avatar" class="comment-avatar">
            <div class="comment-bubble">
                <div class="comment-content">
                    <span class="comment-username"><?php echo htmlspecialchars($comment['Username']); ?>:</span>
                    <span class="comment-text"><?php echo htmlspecialchars($comment['Content']); ?></span>
                </div>
                <div class="comment-actions">
                    <button class="reply-btn" onclick="setReply(<?php echo $post_id; ?>, <?php echo $comment_id; ?>, '<?php echo htmlspecialchars($comment['Username']); ?>')">
                        Trả lời
                    </button>
                </div>
            </div>
        </div>
        <div class="reply-container" id="comment-replies-<?php echo $comment_id; ?>">
            <?php
            // Đệ quy: render các con của bình luận này
            renderComments($post_id, $comments_by_parent, $comment_id);
            ?>
        </div>
        <?php
    }
    echo "</div>";
}
?>