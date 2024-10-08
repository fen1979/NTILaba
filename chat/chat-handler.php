<?php
session_start();
include 'ChatController.php';

// Получаем действие через AJAX
$action = $_POST['action'] ?? '';
$userId = $_POST['user_id'] ?? 1;

switch ($action) {
    case 'sendMessage':
        $receiverId = $_POST['receiver_id'];
        $message = $_POST['message'];
        try {
            $result = ChatController::saveMessage($userId, $receiverId, $message);
        } catch (\RedBeanPHP\RedException\SQL $e) {
            // message collector (text/ color/ auto_hide = true)
            _flashMessage('Error: ' . $e->getMessage(), 'danger');
        }
        echo json_encode($result);
        break;

    case 'getMessages':
        $receiverId = $_POST['receiver_id'];
        $messages = ChatController::getMessages($userId, $receiverId);
        echo json_encode($messages);
        break;

    case 'deleteMessage':
        $messageId = $_POST['message_id'];
        try {
            $result = ChatController::deleteMessage($messageId);
        } catch (\RedBeanPHP\RedException\SQL $e) {
            // message collector (text/ color/ auto_hide = true)
            _flashMessage('Error: ' . $e->getMessage(), 'danger');
        }
        echo json_encode($result);
        break;

    case 'checkNewMessages':
        $newMessagesCount = ChatController::checkNewMessages($userId);
        echo json_encode(['count' => $newMessagesCount]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}