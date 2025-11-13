<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !$conn) {
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$users = [];

try {
    $sql = "SELECT UserId, Username, IsOnline FROM Users WHERE UserId != ?";
    $params = [$current_user_id];
    $types = "i";

    if (!empty($search_query)) {
        // Tìm kiếm theo Username chứa chuỗi tìm kiếm
        $sql .= " AND Username LIKE ?";
        $params[] = "%" . $search_query . "%";
        $types .= "s";
    }

    $sql .= " ORDER BY IsOnline DESC, Username ASC"; // Ưu tiên hiển thị online lên trên

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị CSDL: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $stmt->close();
    $conn->close();

    echo json_encode($users);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    if (isset($stmt) && $stmt) $stmt->close();
    if ($conn) $conn->close();
}
?>