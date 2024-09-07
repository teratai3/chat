<?php

namespace Drupal\chat\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\chat\Traits\ApiFilterTrait;
use Drupal\chat\Service\RateLimiterService;

class ApiMiddleware implements HttpKernelInterface
{
  use ApiFilterTrait;
  protected $httpKernel;
  protected $rateLimiter;

  const METHODS_PERMISSION = [
    "/chat/api/access_token",
    "/chat/api/refresh_token"
  ];

  protected int $rateLimit = 50; // リクエスト上限数
  protected int $timeWindow = 60; // 秒単位の時間

  public function __construct(HttpKernelInterface $http_kernel, RateLimiterService $rate_limiter)
  {
    $this->httpKernel = $http_kernel;
    $this->rateLimiter = $rate_limiter;
    $this->initialize(); // トレイトの初期化メソッドを呼び出し
  }

  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = true): Response
  {

    $path = $request->getPathInfo();

    if (strpos($path, '/chat/api') === 0) {
      // HTTPS接続かどうかをチェック
      if (!$request->isSecure()) {
        return $this->denyAccess('Requires an HTTPS connection.', Response::HTTP_FORBIDDEN);
      }
    }

    if ($request->getMethod() !== 'POST' && in_array($path, self::METHODS_PERMISSION)) {
      return $this->denyAccess('This endpoint only accepts POST requests.', Response::HTTP_METHOD_NOT_ALLOWED);
    }

    if (in_array($path, self::METHODS_PERMISSION)) {
      $ip_address = md5($request->getClientIp());
      //レートリミットをチェック
      if ($this->rateLimiter->checkRateLimit($ip_address, $this->rateLimit, $this->timeWindow)) {
        return $this->denyAccess('Rate limit exceeded.', Response::HTTP_TOO_MANY_REQUESTS);
      }
    }


    // 特定のルート（パス）に対してのみミドルウェアを適用
    if ($path === '/chat/api/refresh_token') {
      // トークンを検証 (refreshトークンとして)
      if (!$this->validateToken($request, 'refresh')) {
        return $this->denyAccess('Access is denied because the credentials are invalid.');
      }
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }
}
