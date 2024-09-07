<?php

namespace Drupal\chat\Command;

use Drush\Commands\DrushCommands;

use Drupal\chat\Model\ChatRoomsModel;

class ChatCommands extends DrushCommands
{
    /**
     * @command chat:delete_empty
     * @aliases chat
     * @description chatRoom削除
     */
    public function delete_empty()
    {
        //トークが1件も無いRoomを削除する
        $this->output()->writeln('Deleting chat rooms without talks...');
        $chatRoomModel = new ChatRoomsModel();
        $deletedCount = $chatRoomModel->deleteChatRoomsWithoutTalks();
        if ($deletedCount > 0) {
            $this->output()->writeln("Successfully deleted {$deletedCount} chat rooms without talks.");
        } else {
            $this->output()->writeln('No chat rooms without talks were found.');
        }
    }
}
