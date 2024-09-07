<?php

namespace Drupal\chat\Model;

class BaseModel
{
  protected string $table_name;
  private $db;

  public function __construct()
  {
    $this->db = \Drupal::database();
  }

  /**
   * データベース接続を取得。
   */
  protected function getDatabase()
  {
    return $this->db;
  }

  /**
   * エンティティをデータベースに保存。
   */
  public function insert(array $data)
  {
    try {
      return $this->getDatabase()->insert($this->table_name)->fields($data)->execute();
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * エンティティを更新。
   */
  public function update(int $id, array $data)
  {
    try {
      $this->getDatabase()->update($this->table_name)
        ->fields($data)
        ->condition('id', $id)
        ->execute();
      return true;
    } catch (\Exception $e) {
      throw $e;
    }
  }



  /**
   * IDでエンティティを削除。
   */
  public function delete(int $id)
  {
    try {
      $this->getDatabase()->delete($this->table_name)->condition('id', $id)->execute();
      return true;
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * IDでエンティティを読み込む。
   */
  public function find(int $id)
  {
    try {
      $query = $this->getDatabase()->select($this->table_name, 't')
        ->fields('t')
        ->condition('id', $id)
        ->execute();

      return $query->fetchAssoc();
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * すべてのエンティティを取得。
   */
  public function findAll()
  {
    try {
      $query = $this->getDatabase()->select($this->table_name, 't')
        ->fields('t')
        ->execute();

      return $query->fetchAll();
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * カスタムクエリでエンティティを検索。
   */
  public function findBy(array $conditions, array $options = [])
  {
    try {
      $query = $this->getDatabase()->select($this->table_name, 't')
        ->fields('t');

      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }

      if (isset($options['order_by'])) {
        foreach ($options['order_by'] as $field => $direction) {
          $query->orderBy($field, $direction);
        }
      }

      if (isset($options['limit'])) {
        $query->range(0, $options['limit']);
        if($options['limit'] === 1){
          return $query->execute()->fetch(); // fetch()で1件だけ取得
        }
      }

      return $query->execute()->fetchAll();
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * カスタムクエリでエンティティを1件取得。
   */
  public function findOneBy(array $conditions)
  {
    try {
      $query = $this->getDatabase()->select($this->table_name, 't')
        ->fields('t');

      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }

      return $query->execute()->fetchAssoc();
    } catch (\Exception $e) {
      throw $e; // 例外を再スロー
    }
  }
}
