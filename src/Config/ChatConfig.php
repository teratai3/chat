<?php

namespace Drupal\chat\Config;


class ChatConfig
{
    const STATUS = [
        'outstanding' => '未対応',
        'processing' => '対応中',
        'closed' => '完了',
    ];

    // jwt用
    const EXPIRATION = 900; // 15分
    const REFRESH_EXPIRATION = 1209600;

    public static function getJwtSecretKey()
    {
        $secret_key = \Drupal::config('chat.settings')->get('jwt_secret_key');

        if (!$secret_key) {
            throw new \Exception('JWT secret key is not set in the configuration.');
        }

        return $secret_key;
    }


    public static function getEncryptionKey()
    {
        $key = \Drupal::config('chat.settings')->get('openssl_key');
        // キーが設定されていない場合、例外を投げる
        if (!$key) {
            throw new \Exception('Encryption key is not set in the configuration.');
        }

        return $key;
    }


    public static function getEncryptionIv()
    {
        // configテーブルからIVを取得
        $iv = \Drupal::config('chat.settings')->get('openssl_iv');

        // IVが設定されていない場合、例外を投げる
        if (!$iv) {
            throw new \Exception('Encryption IV is not set in the configuration.');
        }

        return $iv;
    }

    public static function generateKey(int $length = 12) {
        // 必要な文字セット
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '-_';
    
        // 各セットから最低1文字を確保
        $password = '';
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $special[rand(0, strlen($special) - 1)];
    
        // 残りの文字をランダムに追加
        $allCharacters = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allCharacters[rand(0, strlen($allCharacters) - 1)];
        }
    
        // シャッフルしてランダム化
        return str_shuffle($password);
    }   
}
