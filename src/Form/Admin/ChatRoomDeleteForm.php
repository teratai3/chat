<?php

namespace Drupal\chat\Form\Admin;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

class ChatRoomDeleteForm extends ContentEntityDeleteForm
{

  /**
   * {@inheritdoc}
   */
  public function getQuestion()
  {
    return "このチャットルームを削除してもよろしいですか？";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl()
  {
    // return $this->entity->toUrl('collection');
    return Url::fromRoute('chat_rooms.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText()
  {
    return '削除';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription()
  {
    return 'この操作は元に戻せません。';
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl()
  {
    // chat_rooms.list へのリダイレクトURLを生成
    return Url::fromRoute('chat_rooms.list');
  }
}
