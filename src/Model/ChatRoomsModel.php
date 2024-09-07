<?php

namespace Drupal\chat\Model;

use Drupal\chat\Model\BaseModel;
use Drupal\Core\Database\Database;
use Drupal\chat\Config\ChatConfig;

class ChatRoomsModel extends BaseModel
{
    protected string $table_name = 'chat_rooms';

    public function findAllWithTalkNew()
    {
        //ユーザー入力が無いため直接sqlを記述 入力がある場合はインジェクション対策を施すこと
        $sql = "
        SELECT m.*, 
               latest.message, 
               latest.message_number, 
               latest.latest_created_at, 
               latest.start_created_at
        FROM {chat_rooms} m
        JOIN (
            SELECT t1.chat_room_id, 
                   t1.user_id, 
                   t1.message, 
                   t1.message_number, 
                   t1.created_at AS latest_created_at,
                   (
                       SELECT t2.created_at 
                       FROM {chat_talks} t2 
                       WHERE t2.chat_room_id = t1.chat_room_id 
                       ORDER BY t2.message_number ASC 
                       LIMIT 1
                   ) AS start_created_at
            FROM {chat_talks} t1
            WHERE t1.message_number = (
                SELECT MAX(t2.message_number) 
                FROM {chat_talks} t2 
                WHERE t2.chat_room_id = t1.chat_room_id
            )
        ) AS latest ON m.id = latest.chat_room_id
        ORDER BY m.id DESC";

        $connection = Database::getConnection();

        $query = $connection->query($sql);
        $results = $query->fetchAll();

        return $results;
    }

    public function find(int $id)
    {

        try {
            $query = $this->getDatabase()->select($this->table_name, 't')
                ->fields('t')
                ->condition('id', $id)
                ->execute();

            $result = $query->fetchAssoc();

            if ($result && isset($result['uid'])) {
                //暗号化して返す
                $encrypted_uid = base64_encode(openssl_encrypt($result['uid'], 'aes-256-cbc', ChatConfig::getEncryptionKey(), OPENSSL_RAW_DATA, ChatConfig::getEncryptionIv()));
                $result['uid'] = $encrypted_uid;
            }

            return $result;
        } catch (\Exception $e) {
            // エラー処理
            throw $e;
        }
    }

    /**
     * トークが存在しないチャットルームを削除するメソッド
     */
    public function deleteChatRoomsWithoutTalks()
    {
        // データベースクエリビルダーでサブクエリを作成
        $query = $this->getDatabase()->select($this->table_name, 'cr')->fields('cr', ['id']);
        $query->leftJoin('chat_talks', 'ct', 'cr.id = ct.chat_room_id');
        $query->isNull('ct.chat_room_id');
        // 削除するチャットルームのIDを取得
        $result = $query->execute()->fetchAllAssoc('id');
        $ids = array_keys($result);
        // 該当するチャットルームエンティティを削除
        if (!empty($ids)) {
            $this->getDatabase()->delete($this->table_name)->condition('id', $ids, 'IN')->execute();
        }
        return count($ids);
    }
}
