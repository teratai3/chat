<?php

namespace Drupal\chat\Service;

use Drupal\Core\Database\Database;
use Drupal\chat\Entity\ChatRooms;
use Drupal\chat\Entity\ChatTalks;

class BotService
{
    public function sendBotMessage(int $chatRoomId, string $message = "")
    {
        $connection = Database::getConnection();
        $transaction = $connection->startTransaction();

        try {
            // ChatTalksエンティティを作成
            $chat_talk = ChatTalks::create([
                'user_id' => null,
                'chat_room_id' => $chatRoomId,
                'message' => $message,
                'is_bot' => true,
            ]);

            $result = $chat_talk->save();

            if (!$result) {
                throw new \Exception('Failed to insert bot message.');
            }

            // 挿入されたIDを取得
            $insertedId = $chat_talk->id();

            // 挿入されたメッセージの詳細を取得
            $insertedMessage = ChatTalks::load($insertedId);
            if (!$insertedMessage) {
                throw new \Exception('Failed to retrieve inserted message.');
            }
        } catch (\Exception $e) {
            $transaction->rollback();
            \Drupal::logger('chat')->error('DB error:' . $e->getMessage());
            return false;
        }

        return $insertedMessage;
    }
}
