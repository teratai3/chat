<?php

namespace Drupal\chat\Command;

use Drush\Commands\DrushCommands;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Drupal\chat\Library\Chat;
use Drupal\chat\Services\BotService;

class ChatServerCommands extends DrushCommands
{
    /**
     * @command chat:server
     * @aliases chatserver
     * @description chatサーバー開始
     */
    public function chat_server()
    {
        // サーバー開始
        $this->output()->writeln('Chat server is starting...');
        $bot_service = \Drupal::service('chat.bot_service');
        $server = IoServer::factory(new HttpServer(new WsServer(new Chat($bot_service))), 8080);

        // サーバー起動処理
        $this->output()->writeln('Chat server started.');
        $server->run();
    }
}
