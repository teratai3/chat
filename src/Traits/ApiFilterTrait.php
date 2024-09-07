<?php

namespace Drupal\chat\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\chat\Library\JwtAuthLib;
use Drupal\chat\Model\ChatRoomsModel;
use Drupal\chat\Config\ChatConfig;

trait ApiFilterTrait
{
    protected $jwtAuthLib;
    protected $chatRooms;

    public function initialize()
    {
        $this->jwtAuthLib = new JwtAuthLib();
        $this->chatRooms = new ChatRoomsModel();
    }

    protected function validateToken(Request $request, $token_type = "access")
    {
        $token = $this->getBearerToken($request);

        if (!$token) return false;

        try {
            $token = $this->jwtAuthLib->decode($token);

            if ($token['token_type'] !== $token_type) {
                return false;
            }

            // トークンの検証とユーザーの取得
            $uid = openssl_decrypt(base64_decode($token["sub"]), "aes-256-cbc", ChatConfig::getEncryptionKey(), OPENSSL_RAW_DATA, ChatConfig::getEncryptionIv());
            $data = $this->chatRooms->findBy(['uid' => $uid], ['limit' => 1]);

            if (!$data) return false;

            // 成功時、リクエストにchatRoomUidを設定
            $request->attributes->set('myChatRoomUid', $token["sub"]);
            return true;
        } catch (\Exception $e) {
            \Drupal::logger('chat')->error('Token validation failed: ' . $e->getMessage());
            return false;
        }
    }

    private function getBearerToken(Request $request)
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function denyAccess(string $message, int $statusCode = Response::HTTP_UNAUTHORIZED)
    {
        // アクセス拒否のレスポンス
        $response = new Response();
        $response->setStatusCode($statusCode);
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode(['error' => $message]));
        return $response;
    }
}
