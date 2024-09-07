<?php

namespace Drupal\chat\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Cache\CacheBackendInterface;

class RateLimiterService
{
  protected $cacheBackend;
  public function __construct(CacheBackendInterface $cache_backend)
  {
    $this->cacheBackend = $cache_backend;
  }

  // レートリミットのチェック
  public function checkRateLimit(string $key, int $request_limit, int $time_window)
  {
    //cache_defaultのテーブルに保存される

    $cache_key = $this->getCacheKey($key);

    // 現在のリクエスト数を取得。
    $cache = $this->cacheBackend->get($cache_key);
    $request_count = $cache ? $cache->data : 0;

    // 制限を超えた場合、429エラーレスポンスを返す。
    if ($request_count >= $request_limit) {
      return true;
    } else {
      // リクエスト数を1増加。
      $this->cacheBackend->set($cache_key, $request_count + 1, time() + $time_window);
      return false;
    }
  }

  // キャッシュキーを生成。
  protected function getCacheKey(string $key): string
  {
    return 'rate_limiter_' . $key;
  }
}
