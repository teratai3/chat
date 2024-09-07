<?php

namespace Drupal\chat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\chat\Model\ChatRoomsModel;
use Drupal\chat\Config\ChatConfig;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\chat\Library\JwtAuthLib;


class ChatRoomController extends ControllerBase
{
  protected $chatRooms;
  protected $jwtAuthLib;

  public function __construct()
  {
    $this->chatRooms = new ChatRoomsModel();
    $this->jwtAuthLib = new JwtAuthLib();
  }

  public function index()
  {
    return [
      '#theme' => 'chat_room_front_index',
      // '#cache' => [
      //   'max-age' => 0, // キャッシュを無効化（必要に応じて調整）
      // ],
      '#attached' => [
        'library' => [
          'chat/chat_room_front',
        ],
      ],
    ];
  }
}
