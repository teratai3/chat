<?php

namespace Drupal\chat\Library;
use Drupal\chat\Config\ChatConfig;

class JwtAuthLib
{
    // JWTをエンコードして生成するメソッド
    public function encode($payload)
    {
        try {
            $secretKey = ChatConfig::getJwtSecretKey();
        } catch (\Exception $e) {
            // ログにエラーを記録し、エンコード処理を中断
            \Drupal::logger('chat')->error('JWT secret key not found: ' . $e->getMessage());
            return null;
        }
       

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);

        $base64UrlHeader = $this->base64urlEncode($header);
        $base64UrlPayload = $this->base64urlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
        $base64UrlSignature = $this->base64urlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    // JWTをデコードして検証するメソッド
    public function decode($jwt)
    {
        try {
            $secretKey = ChatConfig::getJwtSecretKey();
        } catch (\Exception $e) {
            // ログにエラーを記録し、デコード処理を中断
            \Drupal::logger('chat')->error('JWT secret key not found: ' . $e->getMessage());
            return null; // デコード失敗時はnullを返す
        }
        
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
        $header = json_decode($this->base64urlDecode($base64UrlHeader), true);

        if (!isset($header['typ']) || !isset($header['alg'])) {
            return null; // subが無い場合は無効
        }

        if ($header['alg'] !== 'HS256' || $header['typ'] !== 'JWT') {
            return null;
        }

        $signature = $this->base64urlDecode($base64UrlSignature);
        $validSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload,$secretKey, true);

        if (!hash_equals($validSignature, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64urlDecode($base64UrlPayload), true);

        if (!isset($payload['sub']) || !isset($payload['iat']) || !isset($payload['exp']) || !isset($payload['token_type'])) {
            return null;
        }

        // `iat` と `exp` の検証
        $now = time();

        if ($payload['iat'] > $now) {
            return null; // トークンが未来の発行時間を持っている場合は無効
        }
     
        if ($payload['exp'] < $now) {
            return null; // トークンが期限切れの場合は無効
        }

        return $payload;
    }

    // Base64URLエンコードを行うメソッド
    private function base64urlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // Base64URLデコードを行うメソッド
    private function base64urlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
