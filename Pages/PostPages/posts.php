<?php
session_start();
require_once '../../Handler/db.php';

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

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhật ký - ChatApp</title>
    <link rel="stylesheet" href="./../../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    <style>
        /* (Toàn bộ CSS của bạn giữ nguyên) */
        /* ... */
        /* (CSS cho .post-card, .reaction-btn, .comment-replies, .options-dropdown, v.v...) */
        /* CSS cho trang Posts */
        .page-content {
            flex-grow: 1; display: flex; justify-content: center;
            padding: 50px 20px; background-color: #1a1a1a;
        }
        .post-feed { width: 100%; max-width: 700px; }
        .post-feed-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px;
        }
        .post-feed-header h1 { color: #f0f0f0; letter-spacing: 2px; }
        .btn-create-post {
            padding: 10px 20px; background-color: #ff6666; color: #1a1a1a;
            text-decoration: none; border-radius: 5px; font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .btn-create-post:hover { background-color: #ff8080; }

        /* Card bài đăng */
        .post-card {
            background-color: #2a2a2a; border-radius: 8px; margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); overflow: hidden;
        }
        .post-header { display: flex; align-items: center; padding: 15px 20px; }
        .post-avatar {
            width: 45px; height: 45px; border-radius: 50%;
            margin-right: 15px; border: 2px solid #444;
        }
        .post-user-info { display: flex; flex-direction: column; flex-grow: 1; }
        .post-username { font-weight: bold; color: #ff6666; font-size: 1.1em; }
        .post-time { font-size: 0.8em; color: #aaa; }
        .post-content {
            padding: 0 20px 15px 20px; line-height: 1.6;
            white-space: pre-wrap; word-wrap: break-word;
        }
        .post-image {
            width: 100%; max-height: 500px; object-fit: cover;
            background-color: #333;
        }

        /* Tương tác: Reactions (AJAX) */
        .post-interactions {
            padding: 10px 20px; border-top: 1px solid #333;
            display: flex; justify-content: space-between; align-items: center;
        }
        .reaction-buttons-wrapper {
            display: flex;
            gap: 5px;
        }
        .reaction-btn {
            background: #333;
            border: 1px solid #444;
            border-radius: 5px;
            font-size: 1.2em;
            cursor: pointer;
            padding: 5px 8px;
            transition: transform 0.1s;
        }
        .reaction-btn:hover {
            transform: scale(1.1);
            background: #444;
        }
        .reaction-btn.active { /* Nút được chọn */
            background: #555;
            border-color: #ff6666;
            transform: scale(1.1);
        }

        .post-stats {
            font-size: 0.9em; color: #aaa;
        }
        .top-emotes-display { margin-right: 5px; }

        /* Khu vực bình luận */
        .comment-section {
            padding: 10px 20px 15px 20px;
            border-top: 1px solid #333;
            background-color: #222;
        }
        .comments-list { 
             display: flex; flex-direction: column; gap: 10px;
        }
        .comment { 
            display: flex; gap: 10px; 
            font-size: 0.9em;
        }
        .comment-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            flex-shrink: 0;
        }
        .comment-bubble {
            display: flex; flex-direction: column;
            width: 100%;
        }
        .comment-content {
            background-color: #333; padding: 8px 12px;
            border-radius: 10px; display: inline-block; max-width: fit-content;
        }
        .comment-username { font-weight: bold; color: #66ccff; margin-right: 5px; }
        .comment-text { color: #f0f0f0; }
        
        /* Bình luận trả lời */
        .comment-actions { margin-top: 3px; }
        .reply-btn {
            background: none; border: none; color: #aaa;
            font-size: 0.8em; cursor: pointer; padding: 0;
            text-decoration: none;
        }
        .reply-btn:hover { color: #f0f0f0; }
        .comment-replies { 
            margin-left: 40px; 
            padding-top: 10px;
            display: flex; flex-direction: column; gap: 10px;
        }
        .reply-container { 
             margin-left: 40px; 
             display: flex; flex-direction: column; gap: 10px;
             padding-top: 10px;
        }

        /* Form đăng bình luận */
        .comment-form-container { padding-top: 15px; border-top: 1px solid #333; margin-top: 15px; }
        .comment-form { display: flex; gap: 10px; }
        .comment-input {
            flex-grow: 1; padding: 8px 12px; border-radius: 15px;
            border: none; background-color: #333; color: #f0f0f0;
            font-family: 'Roboto Mono', monospace;
        }
        .comment-submit-btn {
            background-color: #ff6666; border: none; color: #1a1a1a;
            padding: 8px 15px; border-radius: 15px;
            font-weight: bold; cursor: pointer;
        }
        .reply-info {
            font-size: 0.8em; color: #aaa; margin-bottom: 5px;
        }
        .cancel-reply-btn {
            background: none; border: none; color: #ff6666;
            cursor: pointer; margin-left: 5px;
        }

        /* CSS CHO MENU TÙY CHỌN (Sửa/Xóa) */
        .post-options { position: relative; }
        .options-btn {
            background: none; border: none; color: #aaa;
            font-size: 1.5em; cursor: pointer; padding: 5px; line-height: 1;
        }
        .options-btn:hover { color: #f0f0f0; }
        .options-dropdown {
            display: none; position: absolute; right: 0; top: 30px;
            background-color: #333; border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
            overflow: hidden; z-index: 10;
        }
        .options-dropdown a, .options-dropdown button {
            display: block; padding: 10px 15px; color: #f0f0f0;
            text-decoration: none; font-size: 0.9em; background: none;
            border: none; width: 100%; text-align: left; cursor: pointer;
        }
        .options-dropdown a:hover, .options-dropdown button:hover { background-color: #444; }
        .options-dropdown .delete-btn:hover,
        .options-dropdown .unfriend-btn:hover,
        .options-dropdown .report-btn:hover {
            background-color: #ff6666;
            color: #1a1a1a;
        }
        .options-dropdown.show { display: block; }
    </style>
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
        <a href="../../index.php">HOME</a>
        <a href="../../Pages/PostPages/posts.php">POSTS</a>
        <a href="../../Pages/ChatPages/chat.php">CHAT</a>
        <a href="../../Pages/FriendPages/friends.php">FRIENDS</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <a href="../../admin_dashboard.php">ADMIN</a>
        <?php endif; ?>
    </nav>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
            <div class="avatar-menu">
                <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'images/default-avatar.jpg'), '/'); ?>
                <img src="../../<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="../profile.php">Chỉnh sửa hồ sơ</a>
                    <a href="../../Handler/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="Pages/login.php" class="btn-text">Login</a>
            <a href="Pages/register.php" class="btn-text">Register</a>
        <?php endif; ?>
    </div>
</header>

    <main class="page-content">
        <div class="post-feed">
            
            <div class="post-feed-header">
                <h1>Nhật ký</h1>
                <a href="../PostPages/create_post.php" class="btn-create-post">Tạo bài đăng</a>
            </div>

            <?php
            
            // [CẬP NHẬT] Lấy tất cả bài đăng, LỌC RA người bị ẩn và người đã chặn
            $sql_posts = "SELECT p.PostId, p.UserId, p.Content, p.ImagePath, p.PostedAt, 
                                 u.Username, u.AvatarPath 
                          FROM posts p
                          JOIN users u ON p.UserId = u.UserId
                          WHERE 
                              p.UserId NOT IN (SELECT HiddenId FROM hidden_feeds WHERE HiderId = ?)
                              AND
                              p.UserId NOT IN (SELECT BlockerId FROM blocked_users WHERE BlockedId = ?)
                          ORDER BY p.PostedAt DESC";
            
            $stmt_posts = $conn->prepare($sql_posts);
            // Bind 2 ID của user hiện tại vào 2 câu sub-query
            $stmt_posts->bind_param("ii", $current_user_id, $current_user_id);
            $stmt_posts->execute();
            $result_posts = $stmt_posts->get_result();
            
            if ($result_posts->num_rows > 0):
                while($post = $result_posts->fetch_assoc()):
                    $post_id = $post['PostId'];
            ?>
            
                <div class="post-card" id="post-<?php echo $post_id; ?>">
                    <div class="post-header">
                        <img src="<?php echo htmlspecialchars($post['AvatarPath']); ?>" alt="Avatar" class="post-avatar">
                        <div class="post-user-info">
                            <span class="post-username"><?php echo htmlspecialchars($post['Username']); ?></span>
                            <span class="post-time"><?php echo date('d/m/Y \lúc H:i', strtotime($post['PostedAt'])); ?></span>
                        </div>
                        
                        <div class="post-options">
                            <button class="options-btn" onclick="toggleOptions(<?php echo $post_id; ?>)">&#8942;</button>
                            <div class="options-dropdown" id="options-<?php echo $post_id; ?>">
                                <?php if ($post['UserId'] == $current_user_id): // Nếu là bài của TÔI ?>
                                    <a href="edit_post.php?id=<?php echo $post_id; ?>">Chỉnh sửa</a>
                                    <button class="delete-btn" onclick="deletePost(<?php echo $post_id; ?>)">Xóa bài đăng</button>
                                <?php else: // Nếu là bài của NGƯỜI KHÁC ?>
                                    <button onclick="hideFeed(<?php echo $post['UserId']; ?>, <?php echo $post_id; ?>)">Ẩn nhật ký của <?php echo htmlspecialchars($post['Username']); ?></button>
                                    <button onclick="blockUser(<?php echo $post['UserId']; ?>)">Chặn <?php echo htmlspecialchars($post['Username']); ?> xem nhật ký</button>
                                    <button class="report-btn" onclick="reportPost(<?php echo $post_id; ?>)">Báo xấu</button>
                                    <button class="unfriend-btn" onclick="unfriendUser(<?php echo $post['UserId']; ?>)">Xóa bạn</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['Content'])); ?>
                    </div>
                    
                    <?php if ($post['ImagePath']): ?>
                        <img src="../../<?php echo htmlspecialchars($post[ 'ImagePath']); ?>" alt="Ảnh bài đăng" class="post-image">
                    <?php endif; ?>

                    <div class="post-interactions">
                        <div class="reaction-buttons-wrapper" id="reaction-wrapper-<?php echo $post_id; ?>">
                            <?php
                            // Lấy reaction của user HIỆN TẠI
                            $sql_user_emote = "SELECT EmoteId FROM postemotes WHERE PostId = ? AND UserId = ?";
                            $stmt_user_emote = $conn->prepare($sql_user_emote);
                            $stmt_user_emote->bind_param("ii", $post_id, $current_user_id);
                            $stmt_user_emote->execute();
                            $user_emote_result = $stmt_user_emote->get_result();
                            $user_emote_id = ($user_emote_result->num_rows > 0) ? $user_emote_result->fetch_assoc()['EmoteId'] : 0;
                            $stmt_user_emote->close();
                            
                            // Hiển thị 5 nút
                            foreach ($emotes_map as $emote_id => $emote):
                                $is_active = ($user_emote_id == $emote_id) ? 'active' : '';
                            ?>
                                <button class="reaction-btn <?php echo $is_active; ?>" 
                                        data-emote-id="<?php echo $emote_id; ?>"
                                        onclick="handleReaction(<?php echo $post_id; ?>, <?php echo $emote_id; ?>)">
                                    <?php echo $emote['unicode']; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="post-stats" id="post-stats-<?php echo $post_id; ?>">
                            <?php
                            // Lấy tổng count và top emotes
                            $sql_stats = "SELECT EmoteId, COUNT(*) as Count 
                                          FROM postemotes 
                                          WHERE PostId = ? 
                                          GROUP BY EmoteId 
                                          ORDER BY Count DESC";
                            $stmt_stats = $conn->prepare($sql_stats);
                            $stmt_stats->bind_param("i", $post_id);
                            $stmt_stats->execute();
                            $stats_result = $stmt_stats->get_result();
                            $total_reactions = 0;
                            $top_emotes_html = '';
                            while($row = $stats_result->fetch_assoc()) {
                                $total_reactions += $row['Count'];
                                $top_emotes_html .= $emotes_map[$row['EmoteId']]['unicode'];
                            }
                            $stmt_stats->close();
                            ?>
                            <span class="top-emotes-display"><?php echo $top_emotes_html; ?></span>
                            <span class="total-reactions-count"><?php echo $total_reactions > 0 ? $total_reactions : ''; ?></span>
                        </div>
                    </div>

                    <div class="comment-section">
                        <?php
                        // Lấy và sắp xếp TẤT CẢ bình luận
                        $sql_comments = "SELECT c.CommentId, c.Content, c.ParentCommentId, u.Username, u.AvatarPath
                                         FROM comments c
                                         JOIN users u ON c.UserId = u.UserId
                                         WHERE c.PostId = ?
                                         ORDER BY c.CommentedAt ASC";
                        $stmt_comments = $conn->prepare($sql_comments);
                        $stmt_comments->bind_param("i", $post_id);
                        $stmt_comments->execute();
                        $result_comments = $stmt_comments->get_result();
                        
                        // Sắp xếp vào mảng cây
                        $comments_by_parent = [];
                        while($comment = $result_comments->fetch_assoc()) {
                            $comments_by_parent[$comment['ParentCommentId']][] = $comment;
                        }
                        $stmt_comments->close();

                        // Gọi hàm đệ quy để render bình luận (chỉ render gốc)
                        renderComments($post_id, $comments_by_parent, NULL);
                        ?>

                        <div class="comment-form-container" id="comment-form-<?php echo $post_id; ?>">
                            
                            <div class="reply-info" id="reply-info-<?php echo $post_id; ?>" style="display: none;">
                                Đang trả lời <span id="reply-username-<?php echo $post_id; ?>"></span>
                                <button class="cancel-reply-btn" onclick="cancelReply(<?php echo $post_id; ?>)">[Hủy]</button>
                            </div>

                            <form class="comment-form" onsubmit="submitComment(event, <?php echo $post_id; ?>)">
                                <input type="hidden" id="parent-id-input-<?php echo $post_id; ?>" value="0">
                                <input type="text" id="comment-input-<?php echo $post_id; ?>" class="comment-input" placeholder="Viết bình luận..." required>
                                <button type="submit" class="comment-submit-btn">Gửi</button>
                            </form>
                        </div>
                    </div>

                </div>
                <?php
                endwhile;
            else:
                echo "<p style='text-align: center; color: #aaa;'>Chưa có bài đăng nào.</p>";
            endif;
            
            // Đóng kết nối cuối cùng
            $conn->close();
            ?>

        </div>
    </main>

    <script>
        // TẤT CẢ ĐỀU DÙNG AJAX

        // Truyền dữ liệu từ PHP sang JS
        const emotesMap = <?php echo json_encode($emotes_map); ?>;
        const currentUsername = <?php echo json_encode($current_username); ?>;
        const currentUserAvatar = <?php echo json_encode($current_user_avatar); ?>;
        
        // Hàm tiện ích để tránh lỗi XSS
        function htmlspecialchars(str) {
            if (typeof str !== 'string') return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        // -----------------------------
        // XỬ LÝ REACTION (AJAX)
        // -----------------------------
        function handleReaction(postId, emoteId) {
            const statsContainer = document.getElementById(`post-stats-${postId}`);
            const topEmotesSpan = statsContainer.querySelector('.top-emotes-display');
            const totalCountSpan = statsContainer.querySelector('.total-reactions-count');
            const buttonWrapper = document.getElementById(`reaction-wrapper-${postId}`);
            const allButtons = buttonWrapper.querySelectorAll('.reaction-btn');

            fetch('./../../Handler/PostHandler/handle-reaction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}&emote_id=${emoteId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    totalCountSpan.textContent = data.reactionCount > 0 ? data.reactionCount : '';
                    let topHtml = '';
                    data.topEmotes.forEach(id => {
                        topHtml += emotesMap[id]['unicode'];
                    });
                    topEmotesSpan.textContent = topHtml;
                    allButtons.forEach(btn => btn.classList.remove('active'));
                    if (data.currentUserEmote > 0) {
                        const activeButton = buttonWrapper.querySelector(`.reaction-btn[data-emote-id="${data.currentUserEmote}"]`);
                        if(activeButton) activeButton.classList.add('active');
                    }
                } else {
                    alert('Lỗi Reaction: ' + data.message);
                }
            })
            .catch(error => console.error('Lỗi khi reaction:', error));
        }

        // -----------------------
        // XỬ LÝ COMMENT (AJAX)
        // -----------------------
        function setReply(postId, commentId, username) {
            document.getElementById(`parent-id-input-${postId}`).value = commentId;
            document.getElementById(`reply-username-${postId}`).textContent = username;
            document.getElementById(`reply-info-${postId}`).style.display = 'block';
            document.getElementById(`comment-input-${postId}`).focus();
        }

        function cancelReply(postId) {
            document.getElementById(`parent-id-input-${postId}`).value = '0';
            document.getElementById(`reply-info-${postId}`).style.display = 'none';
        }

        function createCommentHtml(comment, postId) {
            const avatar = comment.AvatarPath ? htmlspecialchars(comment.AvatarPath) : htmlspecialchars(currentUserAvatar);
            const username = comment.Username ? htmlspecialchars(comment.Username) : htmlspecialchars(currentUsername);
            return `
                <div class="comment" id="comment-${comment.CommentId}">
                    <img src="${avatar}" alt="Avatar" class="comment-avatar">
                    <div class="comment-bubble">
                        <div class="comment-content">
                            <span class="comment-username">${username}:</span>
                            <span class="comment-text">${htmlspecialchars(comment.Content)}</span>
                        </div>
                        <div class="comment-actions">
                            <button class="reply-btn" onclick="setReply(${postId}, ${comment.CommentId}, '${username}')">Trả lời</button>
                        </div>
                    </div>
                </div>
                <div class="reply-container" id="comment-replies-${comment.CommentId}"></div>
            `;
        }

        function submitComment(event, postId) {
            event.preventDefault(); 
            const input = document.getElementById(`comment-input-${postId}`);
            const parentIdInput = document.getElementById(`parent-id-input-${postId}`);
            const content = input.value.trim();
            const parentId = parentIdInput.value;
            
            if (content === '') return;

            fetch('./../../Handler/PostHandler/add-comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}&content=${encodeURIComponent(content)}&parent_id=${parentId}`
            })
            .then(response => {
                if (!response.ok) { throw new Error(`Lỗi mạng: ${response.statusText}`); }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    input.value = '';
                    cancelReply(postId);
                    const comment = data.comment;
                    const newCommentHtml = createCommentHtml(comment, postId);

                    if (comment.ParentCommentId === null || comment.ParentCommentId == 0) {
                        let list = document.querySelector(`#post-${postId} .comments-list`);
                        if (!list) {
                            const commentSection = document.querySelector(`#post-${postId} .comment-section`);
                            const formContainer = document.querySelector(`#post-${postId} .comment-form-container`);
                            list = document.createElement('div');
                            list.className = 'comments-list';
                            commentSection.insertBefore(list, formContainer);
                        }
                        list.innerHTML += newCommentHtml;
                    } else {
                        const replyContainer = document.getElementById(`comment-replies-${comment.ParentCommentId}`);
                        if (replyContainer) {
                            replyContainer.innerHTML += newCommentHtml;
                        } else {
                            document.querySelector(`#post-${postId} .comments-list`).innerHTML += newCommentHtml;
                        }
                    }
                } else {
                    alert('Lỗi từ Server: ' + data.message);
                }
            })
            .catch(error => {
                alert(`LỖI JAVASCRIPT:\n${error.message}`);
                console.error('Lỗi khi bình luận:', error);
            });
        }

        // -----------------------
        // XỬ LÝ MENU TÙY CHỌN (Sửa/Xóa)
        // -----------------------
        function toggleOptions(postId) {
            document.getElementById(`options-${postId}`).classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.matches('.options-btn')) {
                var dropdowns = document.getElementsByClassName("options-dropdown");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
        
        function deletePost(postId) {
            if (!confirm('Bạn có chắc chắn muốn xóa bài đăng này không?')) { return; }
            fetch('./../../Handler/PostHandler/delete-post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const postElement = document.getElementById(`post-${postId}`);
                    if (postElement) { postElement.remove(); }
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => console.error('Lỗi khi xóa bài đăng:', error));
        }
        
        // -----------------------
        // [MỚI] 4 HÀM CHO CÁC TÍNH NĂNG MỚI
        // -----------------------

        function unfriendUser(userId) {
            if (!confirm('Bạn có chắc chắn muốn hủy kết bạn với người này?')) { return; }
            fetch('./../../Handler/PostHandler/unfriend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message); // Thông báo thành công hoặc lỗi
            })
            .catch(error => console.error('Lỗi khi hủy kết bạn:', error));
        }

        function hideFeed(userId, postId) {
            if (!confirm('Bạn có muốn ẩn tất cả bài đăng từ người này?')) { return; }
            fetch('./../../Handler/PostHandler/hide-feed.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    // Ẩn tất cả bài đăng của user này khỏi DOM
                    document.querySelectorAll(`.post-card[data-user-id="${userId}"]`).forEach(post => post.remove());
                    // Lưu ý: Cần thêm `data-user-id` vào thẻ `.post-card`
                    // Hoặc đơn giản là tải lại trang:
                    location.reload(); 
                }
            })
            .catch(error => console.error('Lỗi khi ẩn feed:', error));
        }

        function blockUser(userId) {
            if (!confirm('Người này sẽ không thấy bài đăng của bạn nữa. Bạn chắc chứ?')) { return; }
            fetch('./../../Handler/Post/php-block-user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
            })
            .catch(error => console.error('Lỗi khi chặn:', error));
        }

        function reportPost(postId) {
            if (!confirm('Bạn có chắc chắn muốn báo xấu bài đăng này?')) { return; }
            fetch('./../../Handler/PostHandler/report-post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
            })
            .catch(error => console.error('Lỗi khi báo xấu:', error));
        }

                // Chờ cho toàn bộ trang được tải xong
        document.addEventListener('DOMContentLoaded', function() {
            
            const avatarBtn = document.getElementById('avatarBtn');
            const avatarDropdown = document.getElementById('avatarDropdown');

            // Kiểm tra xem các phần tử này có tồn tại không
            // (vì khách truy cập sẽ không thấy chúng)
            if (avatarBtn && avatarDropdown) {
                
                // 1. Khi nhấp vào avatar
                avatarBtn.addEventListener('click', function(event) {
                    // Ngăn sự kiện click lan ra ngoài
                    event.stopPropagation(); 
                    
                    // Hiển thị hoặc ẩn dropdown
                    avatarDropdown.classList.toggle('open');
                });

                // 2. Khi nhấp ra ngoài (bất cứ đâu trên trang)
                document.addEventListener('click', function(event) {
                    // Nếu dropdown đang mở và cú click không nằm trong dropdown
                    if (avatarDropdown.classList.contains('open') && !avatarDropdown.contains(event.target)) {
                        avatarDropdown.classList.remove('open');
                    }
                });
            }
        });
    </script>

</body>
</html>