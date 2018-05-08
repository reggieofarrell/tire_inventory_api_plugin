<?php
require_once 'Record.php';

/**
 *
 */
class UserMeta extends Record
{
  protected static $tableName = "at_user_meta";
  protected static $primaryKey = "UserMetaID";

  static function custom_get_sql($options = array()) {
    $where = '';
    if (isset($options['user_id'])) {
      $where .= sprintf(" AND UserID = %u", $options['user_id']);
    }

    if (isset($options['where'])) {
      $where = 'AND ' . $options['where'];
    }

    $sql = "SELECT *
      FROM at_user_meta
      WHERE 1 $where";

    return $sql;
  }

  static function get_by_user_id($user_id) {
    $sql = self::custom_get_sql(array('user_id' => $user_id));
    return static::custom_single($sql);
  }

}

?>
