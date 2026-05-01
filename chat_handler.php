<?php
session_start();
require 'db.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ==========================================
    // USER DASHBOARD ACTIONS
    // ==========================================
    case 'getUnreadCount':
        // DITO TAYO MAG-UUPDATE NG STATUS PARA LAGI SIYANG ONLINE
        // Tuwing nagche-check ang dashboard kung may message, i-u-update din natin ang 'last_active'
        $update_status = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
        $update_status->bind_param("i", $user_id);
        $update_status->execute();

        $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM chats WHERE user_id = ? AND sender = 'admin' AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode(['status' => 'success', 'count' => (int)$result['unread']]);
        break;

    case 'markAsRead':
        $stmt = $conn->prepare("UPDATE chats SET is_read = 1 WHERE user_id = ? AND sender = 'admin' AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        break;

    case 'getMessages':
        $stmt = $conn->prepare("SELECT sender, message, created_at FROM chats WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = [];
        while($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        echo json_encode(['status' => 'success', 'messages' => $messages]);
        break;

    case 'sendMessage':
        $message = trim($_POST['message'] ?? '');
        if ($message !== '') {
            $stmt = $conn->prepare("INSERT INTO chats (user_id, sender, message, is_read, created_at) VALUES (?, 'user', ?, 0, NOW())");
            $stmt->bind_param("is", $user_id, $message);
            $stmt->execute();
            echo json_encode(['status' => 'success']);
        }
        break;

    // ==========================================
    // ADMIN ACTIONS (CHAT MANAGEMENT)
    // ==========================================
    case 'getAdminMessages':
        if ($role !== 'admin') exit;
        
        // I-update din ang last_active ng Admin para makita nilang online ang Admin
        $conn->query("UPDATE users SET last_active = NOW() WHERE id = " . (int)$user_id);

        $target_user = $_GET['target_user'] ?? 0;
        $stmt = $conn->prepare("SELECT sender, message, created_at FROM chats WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $target_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = [];
        while($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        echo json_encode(['status' => 'success', 'messages' => $messages]);
        break;

    case 'sendAdminMessage':
        if ($role !== 'admin') exit;
        $target_user = $_POST['target_user'] ?? 0;
        $message = trim($_POST['message'] ?? '');
        
        if ($message !== '' && $target_user > 0) {
            $stmt = $conn->prepare("INSERT INTO chats (user_id, sender, message, is_read, created_at) VALUES (?, 'admin', ?, 0, NOW())");
            $stmt->bind_param("is", $target_user, $message);
            $stmt->execute();
            echo json_encode(['status' => 'success']);
        }
        break;

    case 'markAdminAsRead':
        if ($role !== 'admin') exit;
        $target_user = $_POST['target_user'] ?? 0;
        $stmt = $conn->prepare("UPDATE chats SET is_read = 1 WHERE user_id = ? AND sender = 'user' AND is_read = 0");
        $stmt->bind_param("i", $target_user);
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid Action']);
        break;
}
?>