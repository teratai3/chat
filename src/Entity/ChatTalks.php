<?php

namespace Drupal\chat\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * @ContentEntityType(
 *   id = "chat_talks",
 *   label = @Translation("チャットトーク"),
 *   label_collection = @Translation("チャットトーク"),
 *   base_table = "chat_talks",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "message"
 *   },
 *  handlers = {
 *  "storage_schema" = "Drupal\chat\ChatStorageSchema",
 *  },
 *   no_ui = TRUE
 * )
 */
class ChatTalks extends ContentEntityBase
{
    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel('ユーザーid')
            ->setSetting('target_type', 'user')
            ->setDefaultValue(null)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'entity_reference_label',
                'weight' => 1,
            ]);

        $fields['chat_room_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel('チャットルームid')
            ->setSetting('target_type', 'chat_rooms')
            ->setRequired(true)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'entity_reference_label',
                'weight' => 2,
            ]);

        $fields['message'] = BaseFieldDefinition::create('string_long')
            ->setLabel('メッセージ')
            ->setRequired(true)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'text_long',
                'weight' => 3,
            ]);

        $fields['message_number'] = BaseFieldDefinition::create('integer')
            ->setLabel('メッセージ番号')
            ->setRequired(true)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'number_integer',
                'weight' => 4,
            ]);

        $fields['is_bot'] = BaseFieldDefinition::create('boolean')
            ->setLabel('botフラグ')
            ->setDefaultValue(false)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'boolean',
                'weight' => 5,
            ]);

        $fields['created_at'] = BaseFieldDefinition::create('created')
            ->setLabel("作成日時")->setRequired(true);

        $fields['updated_at'] = BaseFieldDefinition::create('changed')
            ->setLabel("更新日時")->setRequired(true);


        return $fields;
    }
}
