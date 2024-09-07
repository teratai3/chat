<?php

namespace Drupal\chat\Service;

use Drupal\chat\Library\JwtAuthLib;
use Ratchet\ConnectionInterface;
use Drupal\chat\Entity\ChatRooms;
use Drupal\Core\Database\Database;
use Drupal\chat\Config\ChatConfig;

class AuthService
{
    protected $jwtAuthLib;
    protected $conn;

    public function __construct(ConnectionInterface $conn)
    {
        $this->jwtAuthLib = new JwtAuthLib();
        $this->conn = $conn;
    }

    // トークンを検証する
    public function checkToken()
    {
        // クエリパラメータからトークンを取得
        $queryParams = [];
        parse_str($this->conn->httpRequest->getUri()->getQuery(), $queryParams);
        $token = !empty($queryParams['token']) ? $queryParams['token'] : null;
        $token = $this->validateToken($token);

        // トークンが不正な場合はfalse
        return $token ? $token : false;
    }

    // ルームを検証
    public function checkRoom($token)
    {
        if (isset($token["user_id"])) {
            // 管理者用の認証
            $data = $this->validateChat($token);
        } else {
            // フロント用の認証
            $data = $this->validateFrontChat($token);
        }

        return $data ? $data : false;
    }

    protected function validateToken($token)
    {
        if (!$token) return false;

        // JwtAuthLib を使用してトークンをデコードし、検証
        $decoded = $this->jwtAuthLib->decode($token);

        if ($decoded === null) return null;

        if ($decoded['token_type'] === 'access') {
            return $decoded;
        } else {
            return null;
        }
    }

    protected function validateChat($token = [])
    {
        try {
            $uid = openssl_decrypt(base64_decode($token["sub"]), "aes-256-cbc", ChatConfig::getEncryptionKey(), OPENSSL_RAW_DATA, ChatConfig::getEncryptionIv());
            $chat_room = \Drupal::entityTypeManager()->getStorage('chat_rooms')->loadByProperties(['uid' => $uid]);
        } catch (\Exception $e) {
            \Drupal::logger('chat')->error('Decryption failed: @message', ['@message' => $e->getMessage()]);
            return null;
        }

        return $chat_room ? reset($chat_room) : null;
    }

    protected function validateFrontChat($token = [])
    {
        $connection = Database::getConnection();
        $transaction = $connection->startTransaction();
        try {
            $uid = openssl_decrypt(base64_decode($token["sub"]), "aes-256-cbc", ChatConfig::getEncryptionKey(), OPENSSL_RAW_DATA, ChatConfig::getEncryptionIv());
            $chat_room = \Drupal::entityTypeManager()
                ->getStorage('chat_rooms')
                ->loadByProperties(['uid' => $uid]);

            if (!$chat_room) {
                // データが見つからない場合、新規追加
                $chat_room = ChatRooms::create(['uid' => $uid]);
                $chat_room->save();
            } else {
                $chat_room = reset($chat_room);
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            \Drupal::logger('chat')->error('Decryption failed: @message', ['@message' => $e->getMessage()]);
            return null;
        }

        return $chat_room;
    }
}
