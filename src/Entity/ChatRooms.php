<?php

namespace Drupal\chat\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\chat\Config\ChatConfig;

/**
 * @ContentEntityType(
 *   id = "chat_rooms",
 *   label = @Translation("チャットルーム"),
 *   label_collection = @Translation("チャットルーム"),
 *   base_table = "chat_rooms",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "memo"
 *  },
 *  handlers = {
 * "form" = {
 *  "status_edit" = "Drupal\chat\Form\Admin\ChatRoomStatusForm",
 *  "delete" = "Drupal\chat\Form\Admin\ChatRoomDeleteForm"
 *  },
 *  "storage_schema" = "Drupal\chat\ChatStorageSchema",
 *  }
 * )
 */
class ChatRooms extends ContentEntityBase
{
    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['uid'] = BaseFieldDefinition::create('string')
            ->setLabel('uid')
            ->setSettings([
                'max_length' => 191,
            ])
            ->addConstraint('UniqueField', []);

        $fields['memo'] = BaseFieldDefinition::create('string_long')
            ->setLabel('管理用メモ')
            ->addPropertyConstraints('value', [
                'Length' => [
                    'max' => 1000,
                    'maxMessage' => 'メモは1000文字以内で入力してください。',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 2,
            ]);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel('ステータス')
            ->setSettings([
                'allowed_values' => ChatConfig::STATUS,
            ])
            ->setRequired(true)
            ->setDefaultValue('outstanding')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ]);

        $fields['created_at'] = BaseFieldDefinition::create('created')
            ->setLabel("作成日時")->setRequired(true);

        $fields['updated_at'] = BaseFieldDefinition::create('changed')
            ->setLabel("更新日時")->setRequired(true);

        return $fields;
    }
}
