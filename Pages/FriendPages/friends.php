<?php
require_once '../../Handler/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$userId = $_SESSION['user_id'];
// Lấy username hiện tại nếu đã đăng nhập
$current_username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Bạn bè & Lời mời</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="./../../css/style.css">
<style>
/* 🌿 PASTEL SOCIAL THEME */
:root {
    --color-primary: #6F9DE1;
    --color-primary-dark: #74C0C9;
    --color-secondary: #F1FAEE;
    --color-bg: #EAF4F4;
    --color-card: #FFFFFF;
    --color-text: #2B2D42;
    --color-text-muted: #6C757D;
    --color-success: #81C784;
    --color-error: #E57373;
    --color-accent: #457B9D;
    --color-border: #D0E2E2;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--color-bg);
    color: var(--color-text);
    margin: 0;
}

/* Layout */
.container { display: flex; flex-direction: column; height: calc(100vh - 60px); }
.top-bar {
    display: flex; align-items: center; padding: 12px 20px;
    background: var(--color-secondary);
    border-bottom: 1px solid var(--color-border);
}
.back-btn {
    background: none; border: none; color: var(--color-accent);
    font-size: 20px; margin-right: 10px; cursor: pointer;
    transition: color .2s;
}
.back-btn:hover { color: var(--color-primary-dark); }

/* Search bar */
.search-bar { flex: 1; display: flex; justify-content: center; position: relative; }
.search-bar input {
    width: 60%; padding: 10px 15px; border-radius: 20px;
    border: 1px solid var(--color-border); background: var(--color-card);
    color: var(--color-text); font-size: 15px; outline: none;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.search-popup {
    position: absolute; top: 45px; width: 60%;
    background: var(--color-card); border: 1px solid var(--color-border);
    border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.08);
    max-height: 250px; overflow-y: auto; display: none; z-index: 100;
}
.search-popup div {
    display: flex; align-items: center; padding: 8px 12px; cursor: pointer;
    transition: background .15s;
}
.search-popup div:hover { background: var(--color-secondary); }
.search-popup img {
    border-radius: 50%; width: 32px; height: 32px; margin-right: 10px; object-fit: cover;
}

/* Content */
.content { display: flex; flex: 1; overflow: hidden; }
.left, .right { padding: 15px; overflow-y: auto; background: var(--color-card); }
.left { flex: 2; border-right: 1px solid var(--color-border); }
.right { flex: 1; }
h2 {
    margin-bottom: 10px; font-size: 16px; color: var(--color-accent);
    border-bottom: 1px solid var(--color-border); padding-bottom: 5px;
}
.friend-item, .request-item {
    display: flex; align-items: center; padding: 10px 12px;
    border-radius: 10px; transition: background .15s, border .15s;
    border: 1px solid transparent;
}
.friend-item:hover, .request-item:hover {
    background: var(--color-secondary); border-color: var(--color-border);
}
.avatar-img {
    width: 45px; height: 45px; border-radius: 50%;
    object-fit: cover; border: 1px solid var(--color-border);
}
.friend-info { flex: 1; display: flex; flex-direction: column; margin-left: 10px; }
.friend-info strong { font-weight: 600; color: var(--color-text); }
.friend-info small { color: var(--color-text-muted); font-size: 12px; }
.status-dot { width: 10px; height: 10px; border-radius: 50%; margin-left: 6px; border: 1px solid var(--color-border); }

/* Buttons */
button {
    padding: 6px 10px; border: none; border-radius: 8px; font-size: 13px;
    cursor: pointer; transition: background .2s, transform .1s;
}
button:hover { transform: translateY(-1px); }
button.accept { background: var(--color-success); color: #fff; }
button.reject { background: var(--color-error); color: #fff; }

/* Overlay */
.friend-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.4);
    display: none; justify-content: center; align-items: center;
    z-index: 1000;
}
.friend-overlay .box {
    background: var(--color-card);
    padding: 20px 25px; border-radius: 16px;
    width: 420px; display: flex; align-items: center; gap: 15px;
    border: 1px solid var(--color-border);
    box-shadow: 0 2px 10px rgba(0,0,0,.1); position: relative;
}
.friend-overlay .box img {
    width: 60px; height: 60px; border-radius: 50%;
    object-fit: cover; border: 1px solid var(--color-border);
}
.friend-overlay .box h3 { flex: 1; font-size: 16px; color: var(--color-text); margin: 0; }
.close-btn {
    position: absolute; top: 5px; right: 10px; background: none; border: none;
    color: var(--color-text-muted); font-size: 18px; cursor: pointer;
}
.close-btn:hover { color: var(--color-accent); }
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
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="Pages/profile.php">Chỉnh sửa hồ sơ</a>
                    <a href="../../Handler/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="Pages/login.php" class="btn-text">Login</a>
            <a href="Pages/register.php" class="btn-text">Register</a>
        <?php endif; ?>
    </div>
</header>

<div class="container">
    <div class="top-bar">
        <a class="back-btn" href="index.php">←</a>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Tìm bạn bè...">
            <div id="search-results" class="search-popup"></div>
        </div>
    </div>
    <div class="content">
        <div class="left">
            <h2>Lời mời kết bạn</h2>
            <div id="requests"></div>
        </div>
        <div class="right">
            <h2>Bạn bè của bạn</h2>
            <div id="friends-list"></div>
        </div>
    </div>
</div>

<!-- Overlay -->
<div id="friendOverlay" class="friend-overlay">
    <div class="box">
        <button class="close-btn" type="button" onclick="toggleOverlay(false)">✕</button>
        <img id="overlayAvatar" alt="avatar">
        <h3 id="overlayName"></h3>
        <button id="msgBtn" class="accept">Nhắn tin</button>
        <button id="unfriendBtn" class="reject">Hủy kết bạn</button>
    </div>
</div>

<script>
const api = './../../Handler/FriendHandler/friend-handler.php';
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('search-results');
const overlay = document.getElementById('friendOverlay');
let selectedFriendId = null, cachedFriends = [];

const fetchPost = async (data) =>
  (await fetch(api, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams(data)})).json();

const renderList = (selector, data, template, emptyMsg) => {
  document.querySelector(selector).innerHTML = data.length ? data.map(template).join('') : `<p>${emptyMsg}</p>`;
};

searchInput.addEventListener('input', async e => {
  const q = e.target.value.trim();
  if (!q) return searchResults.style.display = 'none';
  const users = await (await fetch(`./../../Handler/FriendHandler/search_user.php?q=${encodeURIComponent(q)}`)).json();
  renderList('#search-results', users, u => `
    <div onclick="sendFriend(${u.UserId})">
      <img src="${u.AvatarPath || './uploads/default-avatar.jpg'}" onerror="this.src='./uploads/default-avatar.jpg'">
      ${u.Username}
    </div>`, 'Không tìm thấy người dùng nào');
  searchResults.style.display = 'block';
});

document.addEventListener('click', e => {
  if (!searchResults.contains(e.target) && e.target !== searchInput)
    searchResults.style.display = 'none';
});

async function sendFriend(id) {
  const res = await fetchPost({action:'send', friend_id:id});
  alert(res.status==='sent'?'Đã gửi lời mời kết bạn!':'Đã có yêu cầu hoặc đã là bạn!');
}

async function loadRequests() {
  const data = await fetchPost({action:'fetch_requests'});
  renderList('#requests', data, r => `
    <div class="request-item">
      <img src="${r.sender_avatar || './uploads/default-avatar.jpg'}" class="avatar-img">
      <b>${r.sender_name}</b>
      <button onclick="respond(${r.sender_id},'accept')" class="accept">Chấp nhận</button>
      <button onclick="respond(${r.sender_id},'reject')" class="reject">Từ chối</button>
    </div>`, 'Không có lời mời nào.');
}

async function respond(id, type) {
  await fetchPost({action:type, friend_id:id});
  loadRequests(); loadFriends();
}

function toggleOverlay(show, data={}) {
  overlay.style.display = show ? 'flex' : 'none';
  if (show) {
    selectedFriendId = data.id;
    document.getElementById('overlayName').textContent = data.name;
    // Đảm bảo avatar hợp lệ
    const validAvatar = (data.avatar && data.avatar !== 'null' && data.avatar !== 'undefined' && data.avatar.trim() !== '') 
      ? data.avatar 
      : './images/default-avatar.jpg';
    const overlayAvatarEl = document.getElementById('overlayAvatar');
    overlayAvatarEl.src = validAvatar;
    overlayAvatarEl.onerror = function() {
      this.src = './images/default-avatar.jpg';
    };
  } else selectedFriendId = null;
}

function timeAgo(date) {
  const diff = (Date.now() - new Date(date)) / 1000;
  if (diff < 60) return `${Math.floor(diff)}s trước`;
  if (diff < 3600) return `${Math.floor(diff/60)}m trước`;
  if (diff < 86400) return `${Math.floor(diff/3600)}h trước`;
  return `${Math.floor(diff/86400)}d trước`;
}

async function loadFriends() {
  const friends = await fetchPost({action:'fetch_friends'});
  if (JSON.stringify(friends) === JSON.stringify(cachedFriends)) return;
  cachedFriends = friends;
  renderList('#friends-list', friends, f => {
    const color = f.IsOnline ? '#43A047' : '#888';
    const status = f.IsOnline ? 'Online' : (f.LastSeen ? timeAgo(f.LastSeen) : 'Offline');
    const avatar = f.AvatarPath || './uploads/default-avatar.jpg';
    const displayName = (f.FullName || f.Username || 'Unknown').replace(/'/g, "\\'");
    const escapedAvatar = avatar.replace(/'/g, "\\'");
    return `
      <div class="friend-item" onclick="toggleOverlay(true, {id:${f.UserId}, name:'${displayName}', avatar:'${escapedAvatar}'})" style="cursor: pointer;">
        <img src="${avatar}" class="avatar-img" onerror="this.src='./uploads/default-avatar.jpg'">
        <div class="friend-info">
          <strong>${displayName}</strong>
          <small>${status}</small>
        </div>
        <span class="status-dot" style="background:${color};"></span>
      </div>`;
  }, 'Bạn chưa có bạn bè 😢');
}

document.getElementById('msgBtn').onclick = () => selectedFriendId && (location = `chat.php?friend_id=${selectedFriendId}`);
document.getElementById('unfriendBtn').onclick = async () => {
  if (selectedFriendId && confirm('Bạn có chắc chắn muốn hủy kết bạn?')) {
    const res = await fetchPost({action:'unfriend', friend_id:selectedFriendId});
    alert(res.status==='success'?'Đã hủy kết bạn':'Lỗi!');
    toggleOverlay(false);
    loadFriends();
  }
};

loadRequests();
loadFriends();

setInterval(loadFriends, 5000);

setInterval(loadFriends,5000);

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
