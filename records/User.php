<?php
require_once 'Record.php';

class User extends Record {
  protected static $tableName = "";
  protected static $primaryKey = "";

  public static function get_logged_in_user() {
    $user_id = get_current_user_id();
    if ($user_id) {
      return self::get($user_id);
    } else {
      return null;
    }
  }

  static function is_correct_password($password, $user_id = null) {
    $user_id = get_current_user_id();
    $sql = sprintf("SELECT user_pass FROM atwp_users WHERE ID = %d", $user_id);
    $response = static::custom_single($sql);
    $password_hash = $response['user_pass'];
    $wp_user = wp_get_current_user();
    return wp_check_password($password, $password_hash, $wp_user);
  }

  public function is_admin() {
    return $this->data['Role'] === 'admin';
  }

  static function custom_get_sql($options = array()) {
    $where = '';
    if (isset($options['user_id'])) {
      $where .= sprintf(" AND u.ID = %u", $options['user_id']);
    }

    if (isset($options['where'])) {
      $where = 'AND ' . $options['where'];
    }

    $select = '';
    $joins = '';

    if (isset($options['slim']) && $options['slim']) {
      $select = '';
      $joins = '';
    }

    $sql = "SELECT
      u.user_email as Email,
      u.ID as recid,
      CONCAT(um.FirstName, ' ',um.LastName) as DisplayName,
      um.*, r.Name as Role, um.RoleID
      $select
      FROM atwp_users u
      JOIN at_user_meta um
        ON u.ID = um.UserID
      JOIN at_role r
        ON um.RoleID = r.RoleID
      $joins
    WHERE um.Deleted = 0 $where";

    return $sql;
  }

  static function custom_get_one_sql($id) {
    return self::custom_get_sql(array('user_id' => $id));
  }

  public function delete() {
    $user_meta_id = UserMeta::get_by_user_id($this->id)['UserMetaID'];
    $user_meta = UserMeta::get($user_meta_id);

    if( !$user_meta->update(['Deleted' => '1']) ) return false;
  }

}

?>
