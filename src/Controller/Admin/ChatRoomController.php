<?php

namespace Drupal\chat\Controller\Admin;

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
  protected $pagerManager;
  protected int $per_page = 20;
  protected $jwtAuthLib;

  public function __construct(PagerManagerInterface $pagerManager)
  {
    $this->chatRooms = new ChatRoomsModel();
    $this->pagerManager = $pagerManager;
    $this->jwtAuthLib = new JwtAuthLib();
  }

  public function list()
  {
    $page = !empty(\Drupal::request()->query->get('page')) ? \Drupal::request()->query->get('page') : 0;
    $page += 1; //デフォルトが0なので

    $this->pagerManager->createPager(count($this->chatRooms->findAllWithTalkNew()), $this->per_page)->getCurrentPage();

    // 現在のページのデータを取得
    $results = $this->chatRooms->findAllWithTalkNew();

    $datas = array_slice($results, ($page - 1) * $this->per_page, $this->per_page);

    // テーブルヘッダの定義
    $header = [
      'id' => 'ID',
      'message' => 'トーク内容(最後のメッセージ)',
      'status' => 'ステータス',
      'start_created_at' => 'トークの開始時刻',
      'latest_created_at' => '最後のトーク時刻',
      'operations' => '操作',
    ];

    // テーブル行の生成
    $rows = [];
    foreach ((array)$datas as $data) {
      $status_edit = Link::fromTextAndUrl(
        isset(ChatConfig::STATUS[$data->status]) ? ChatConfig::STATUS[$data->status] : "",
        Url::fromRoute('chat_rooms.status_edit', ['chat_rooms' => $data->id])
      )->toString();

      $edit_link = [
        'title' => '編集',
        'url' => Url::fromRoute('chat_rooms.edit', ['id' => $data->id]),
        'weight' => 10,
      ];

      $delete_link = [
        'title' => '削除',
        'url' => Url::fromRoute('chat_rooms.delete', ['chat_rooms' => $data->id]),
        'weight' => 100,
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode(['width' => 880]),
        ],
      ];

      $rows[] = [
        'id' => $data->id,
        'message' => $data->message,
        'status' => $status_edit,
        'start_created_at' => !is_null($data->start_created_at) ? date('Y-m-d H:i', $data->start_created_at) : "",
        'latest_created_at' => !is_null($data->latest_created_at) ? date('Y-m-d H:i', $data->latest_created_at) : "",
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'edit' => $edit_link,
              'delete' => $delete_link,
            ],
            '#attached' => [
              'library' => ['core/drupal.dialog.ajax'],
            ],
          ]
        ]
      ];
    }

    // レンダー配列の作成
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => 'データが存在しません'
    ];

    // ページャーの追加
    $build['pager'] = [
      '#type' => 'pager'
    ];

    return $build;
  }
  public function edit(int $id = 0)
  {
    
    try{
      $data = $this->chatRooms->find($id);
    }catch(\Exception $e){
      $this->messenger()->addError($e->getMessage());
      return new RedirectResponse(Url::fromRoute('chat_rooms.list', [], [])->toString());
    }
    
    
    if (!$data) {
      $this->messenger()->addError('チャットが見つかりませんでした');
      return new RedirectResponse(Url::fromRoute('chat_rooms.list', [], [])->toString());
    }


    $time = time();

    // ペイロードの設定
    $access_payload = [
      'sub' => $data["uid"],
      'user_id' => 1, // ユーザー識別子
      'iat' => $time, // トークンの発行時間
      'exp' => $time + ChatConfig::EXPIRATION,
      'token_type' => 'access'
    ];

    $refresh_payload = [
      'sub' => $data["uid"],
      'user_id' => 1, // ユーザー識別子
      'iat' => $time, // トークンの発行時間
      'exp' => $time + ChatConfig::REFRESH_EXPIRATION,
      'token_type' => 'refresh'
    ];


    $access_token = $this->jwtAuthLib->encode($access_payload);
    $refresh_token = $this->jwtAuthLib->encode($refresh_payload);

   
    if ($access_payload === null || $refresh_token === null) {
      $this->messenger()->addError('トークンが生成出来ませんでした。');
      return new RedirectResponse(Url::fromRoute('chat_rooms.list', [], [])->toString());
    }


    // テンプレートに渡すデータを準備
    return [
      '#theme' => 'chat_room_admin_edit',
      '#chat_room' => [
        "access_token" => $access_token,
        "refresh_token" => $refresh_token
      ],
      // '#cache' => [
      //   'max-age' => 0, // キャッシュを無効化（必要に応じて調整）
      // ],
      '#attached' => [
        'library' => [
          'chat/chat_room_admin',
        ],
      ],
    ];
  }
}
