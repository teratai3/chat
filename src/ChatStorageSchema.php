<?php

namespace Drupal\chat;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class ChatStorageSchema extends SqlContentEntityStorageSchema
{
  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping)
  {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    switch ($table_name) {
      case "chat_rooms":
        if ($field_name === "uid" || $field_name === "status") {
          $schema['fields'][$field_name]['not null'] = true;
        }

        if ($field_name === "uid") {
          // ユニークインデックスを追加
          $schema['unique keys']['uid'] = [$field_name];
        }
        break;
      case "chat_talks":
        if ($field_name === "message_number" || $field_name === "is_bot" || $field_name === "chat_room_id") {
          $schema['fields'][$field_name]['not null'] = true;
        }
        if ($field_name === "is_bot") {
          $schema['fields'][$field_name]['mysql_type'] = 'TINYINT(1)';
          $schema['fields'][$field_name]['default'] = 0;
        }

        break;
    }
    return $schema;
  }
}
