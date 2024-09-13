<?php
class ChatController
{
    // Метод для сохранения нового сообщения
    /**
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function saveMessage($senderId, $receiverId, $message): array
    {
        if (empty($message) || empty($senderId) || empty($receiverId)) {
            return ['status' => 'error', 'message' => 'Invalid message data'];
        }

        $chatEntry = R::dispense('entrychat');
        $chatEntry->sender_id = $senderId;
        $chatEntry->receiver_id = $receiverId;
        $chatEntry->message = $message;
        $chatEntry->deleted = 0;
        $chatEntry->created_at = date('Y-m-d H:i:s');

        R::store($chatEntry);

        return ['status' => 'success', 'message' => 'Message sent'];
    }

    // Метод для удаления сообщения

    /**
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function deleteMessage($messageId): array
    {
        $chatEntry = R::load('entrychat', $messageId);
        if ($chatEntry->id) {
            $chatEntry->deleted = 1; // Мягкое удаление
            R::store($chatEntry);
            return ['status' => 'success', 'message' => 'Message deleted', 'msg_id' => $messageId];
        }
        return ['status' => 'error', 'message' => 'Message not found'];
    }

    // Метод для вывода сообщений между пользователями
    public static function getMessages($senderId, $receiverId): array
    {
        // Используем один запрос с условием OR
        $messages = R::findAll('entrychat', '((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND deleted = 0', [
            $senderId, $receiverId,
            $receiverId, $senderId
        ]);

        // Преобразуем коллекцию объектов RedBeanPHP в обычный массив
        return array_values($messages);
    }

    // Метод для проверки новых сообщений для пользователя
    public static function checkNewMessages($userId): int
    {
        $messages = R::find('entrychat', 'receiver_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND) AND deleted = 0', [$userId]);
        return count($messages);
    }
}
