<?php

namespace Drupal\chat\Form\Admin;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class ChatRoomStatusForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildForm($form, $form_state);

         // 一覧へ戻るボタンのURLを作成
         $url = Url::fromRoute('chat_rooms.list'); // ここで一覧ページのルート名を指定します
        
         // 一覧へ戻るボタンリンクを作成
         $form['actions']['back_to_list'] = [
             '#type' => 'link',
             '#title' => '一覧に戻る',
             '#url' => $url,
             '#attributes' => [
                 'class' => ['button'],
             ],
         ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $entity = $this->getEntity();
        $status = $entity->save();
        $this->messenger()->addMessage('更新に成功しました。');
      
        // フォーム送信後にリダイレクトする
        $form_state->setRedirect('chat_rooms.status_edit', ['chat_rooms' => $entity->id()]);
    }
}
