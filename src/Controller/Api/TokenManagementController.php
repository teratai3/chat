<?php

namespace Drupal\chat\Controller\Api;

use Drupal\Core\Controller\ControllerBase;
use Drupal\chat\Model\ChatRoomsModel;
use Drupal\chat\Config\ChatConfig;
use Drupal\chat\Library\JwtAuthLib;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class TokenManagementController extends ControllerBase
{
    protected $jwtAuthLib;

    public function __construct()
    {
        $this->jwtAuthLib = new JwtAuthLib();
    }

    public function access_token(Request $request)
    {
        $time = time(); // 現在のUNIXタイムスタンプ
        $sub_uid = sha1(uniqid(rand(), true)); // ユニークなIDの生成

        try {
            $sub_uid = base64_encode(openssl_encrypt($sub_uid, 'aes-256-cbc', ChatConfig::getEncryptionKey(), OPENSSL_RAW_DATA, ChatConfig::getEncryptionIv()));
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Encryption failed: ' . $e->getMessage()], 500);
        }


        // ペイロードの設定
        $access_payload = [
            'sub' => $sub_uid,
            'iat' => $time, // トークンの発行時間
            'exp' => $time + ChatConfig::EXPIRATION,
            'token_type' => 'access'
        ];

        $refresh_payload = [
            'sub' => $sub_uid,
            'iat' => $time, // トークンの発行時間
            'exp' => $time + ChatConfig::REFRESH_EXPIRATION,
            'token_type' => 'refresh'
        ];



        $access_token = $this->jwtAuthLib->encode($access_payload);
        $refresh_token = $this->jwtAuthLib->encode($refresh_payload);


        // トークンがnullの場合にエラーを返す
        if ($access_token === null || $refresh_token === null) {
            return new JsonResponse(['error' => 'Token generation failed. Please try again.',], 500);
        }

        return new JsonResponse([
            "access_token" => $access_token,
            "refresh_token" => $refresh_token,
        ]);
    }

    public function refresh_token(Request $request)
    {
        $time = time();
        // リファラ（参照元）を取得
        $referer = $request->headers->get('referer');

        $myChatRoomUid = $request->attributes->get('myChatRoomUid');

        if (!$myChatRoomUid) {
            return new JsonResponse(['error' => 'Chat room UID is missing.',], 400);
        }

        // ペイロードの設定
        $access_payload = [
            'sub' => $myChatRoomUid,
            'iat' => $time, // トークンの発行時間
            'exp' => $time + ChatConfig::EXPIRATION,
            'token_type' => 'access'
        ];

        // 管理者ページからのリクエストかどうかをチェックして、ログイン中なら
        if ($referer && strpos($referer, 'admin') !== false) {
            $current_user = \Drupal::currentUser();
            if ($current_user->isAuthenticated() && $current_user->hasRole('administrator')) {
                $access_payload['user_id'] = $current_user->id();
            }
        }

        $access_token = $this->jwtAuthLib->encode($access_payload);

        if ($access_token === null) {
            return new JsonResponse(['error' => 'Token generation failed. Please try again.',], 500);
        }

        //再生成したアクセストークンを返す
        return new JsonResponse([
            "access_token" => $access_token
        ]);
    }
}
