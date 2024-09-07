<?php

namespace Drupal\chat\Library;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Drupal\chat\Service\AuthService;
use Drupal\Core\Database\Database;
use Drupal\chat\Entity\ChatRooms;
use Drupal\chat\Service\BotService;
use Drupal\chat\Config\BotConfig;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $jwtAuthLib;
    protected $chatRoomsStorage;
    protected $chatTalksStorage;
    protected $botService;
    private $subscriptions;
    private $connectedUsers;

    public function __construct(BotService $botService)
    {
        $this->clients = new \SplObjectStorage;
        $this->jwtAuthLib = new JwtAuthLib();
        $this->chatRoomsStorage = \Drupal::entityTypeManager()->getStorage('chat_rooms');
        $this->chatTalksStorage = \Drupal::entityTypeManager()->getStorage('chat_talks');
        $this->botService = $botService;

        $this->subscriptions = [];
        $this->connectedUsers = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $authService = new AuthService($conn);
        $token = $authService->checkToken();

        if (!$token) {
            \Drupal::logger('chat')->error('Token verification failed for connection: ' . $conn->resourceId);
            $conn->close();
            return;
        }

        $data = $authService->checkRoom($token);


        if (!$data || !$data instanceof ChatRooms) {
            \Drupal::logger('chat')->error('Room verification failed for token');
            $conn->close();
            return;
        }

        $this->clients->attach($conn);
        $this->connectedUsers[$conn->resourceId] = $conn; //チャットを特定するために配列に入れる

        $chatHistory = $this->chatTalksStorage->getQuery()
            ->condition('chat_room_id', $data->get('id')->value)
            ->accessCheck(false)
            ->sort('message_number', 'ASC')
            ->execute();

        $chatHistory = $this->chatTalksStorage->loadMultiple($chatHistory);

        // チャット履歴をクライアントに送信
        if (!empty($chatHistory)) {
            foreach ($chatHistory as $message) {
                $user = $message->get('user_id')->entity;
                $conn->send(json_encode([
                    'message' => $message->get('message')->value,
                    'created_at' => date('Y-m-d H:i:s', $message->get('created_at')->value),
                    'user_flag' => !is_null($message->get('user_id')->target_id) ? true : false,
                    'user_name' => $user?->get('name')?->value,
                    'is_bot' => $message->get('is_bot')->value,
                ]));
            }
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $authService = new AuthService($from);
        $token = $authService->checkToken();
        if (!$token) {
            \Drupal::logger('chat')->error('Token verification failed. Closing connection.');
            $from->close();
            return;
        }

        $data = $authService->checkRoom($token);
        if (!$data) {
            \Drupal::logger('chat')->error('Room verification failed. Closing connection.');
            $from->close();
            return;
        }

        $msgData = json_decode($msg, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Drupal::logger('chat')->error('Invalid JSON format: ' . json_last_error_msg());
            $from->close();
            return;
        }

        if (empty($msgData["command"])) {
            \Drupal::logger('chat')->error('Not command Parameter');
            $from->close();
            return;
        }


        if ($msgData["command"] === "message") {
            if (!isset($msgData['message']) || empty(trim($msgData['message']))) {
                \Drupal::logger('chat')->error('Message content is missing or empty.');
                $from->close();
                return;
            }

            if (mb_strlen($msgData['message']) > 1000) {
                \Drupal::logger('chat')->error('Message content exceeds 1000 characters.');
                $from->close();
                return;
            }

            $connection = Database::getConnection();
            $transaction = $connection->startTransaction();
            // https://www.drupal.org/docs/drupal-apis/database-api/database-transactions
            try {
                if (!empty($token['user_id'])) {
                    $chatRoom = $this->chatRoomsStorage->load($data->get('id')->value);
                    $chatRoom->set('status', 'processing');
                    $chatRoom->save();
                }

                $chatTalk = $this->chatTalksStorage->create([
                    'user_id' => !empty($token['user_id']) ? $token['user_id'] : null,
                    'chat_room_id' => $data->get('id')->value,
                    'message' => $msgData['message'],
                ]);
                $chatTalk->save();
                $insertedMessage = $chatTalk;
            } catch (\Exception $e) {
                $transaction->rollBack();
                $this->getLogger('chat')->error('Database error: ' . $e->getMessage());
                $from->close();
                return;
            }
        }

        switch ($msgData["command"]) {
            case "subscribe":
                $this->subscriptions[$from->resourceId] = $data->get('id')->value;
                break;
            case "message":
                if (isset($this->subscriptions[$from->resourceId])) {
                    $target = $this->subscriptions[$from->resourceId];
                    foreach ($this->subscriptions as $id => $channel) {
                        if ($channel == $target && isset($this->connectedUsers[$id])) {
                            $user = $insertedMessage->get('user_id')->entity;
                            $this->connectedUsers[$id]->send(json_encode([
                                'message' => $insertedMessage->get('message')->value,
                                'created_at' => date('Y-m-d H:i:s', $insertedMessage->get('created_at')->value),
                                'user_flag' => !is_null($insertedMessage->get('user_id')->target_id) ? true : false,
                                'user_name' => $user?->get('name')?->value,
                                'is_bot' => $insertedMessage->get('is_bot')->value,
                            ]));

                            //初回のbotメッセージ
                            if ((int)$insertedMessage->get('message_number')->value === 1 && $insertedMessage?->get('chat_room_id')?->target_id) {
                                $result = $this->botService->sendBotMessage($insertedMessage->get('chat_room_id')->target_id, BotConfig::INITIAL_GREETING);
                                if ($result) {
                                    $this->connectedUsers[$id]->send(json_encode([
                                        'message' => $result->get('message')->value,
                                        'created_at' => date('Y-m-d H:i:s', $result->get('created_at')->value),
                                        'user_flag' => false,
                                        'user_name' => "",
                                        'is_bot' => $result->get('is_bot')->value
                                    ]));
                                } else {
                                    $from->close();
                                    return;
                                }
                            }
                        }
                    }
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        unset($this->connectedUsers[$conn->resourceId], $this->subscriptions[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // エラーログを記録する
        \Drupal::logger('chat')->error('An error occurred on connection ' . $conn->resourceId . ': ' . $e->getMessage());
        // 必要に応じて、例外のトレース情報もログに記録する
        \Drupal::logger('chat')->error('Exception trace: ' . $e->getTraceAsString());
        $conn->close();
    }
}
