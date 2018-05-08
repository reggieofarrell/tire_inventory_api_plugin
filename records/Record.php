<?php
$path = $_SERVER['DOCUMENT_ROOT'];

include_once $path . '/wp-content/plugins/tire_inventory_api_plugin/Timer.php';
include_once $path . '/wp-content/plugins/tire_inventory_api_plugin/db-connector.php';

abstract class Record {

  public $id;
  public $data;

  public static function get_tablename() {
    return static::$tableName;
  }

  public static function get_primary_key() {
    return static::$primaryKey;
  }

  function __construct($id = null, $custom_sql = null, $options = array())
  {
    $table = static::get_tablename();
    $timer = new Timer("$table GET 1");
    $this->id = $id;
    $db = inventory_db_connect();
    $sql = ($custom_sql) ? $custom_sql : static::get_one_sql($this->id, $options);
    //make query
    if ($result = mysqli_query($db, $sql) ) {
      if (mysqli_num_rows($result) > 0) {
        $this->data = mysqli_fetch_array($result, MYSQLI_ASSOC);
      }
      mysqli_free_result($result);
    } else {
      // sql error
      Timer::log_error(mysqli_error($db), get_called_class());
    }
    //close connection and send responds
    inventory_db_close($db);
    $timer->end();
  }

  static function get($id = null, $options = array())
  {
    if ($id) {
      return new static($id, null, $options);
    } else {
      return static::get_all_records($options);
    }
  }

  static function get_all_records($options = array())
  {
    $table = static::get_tablename();
    $primary_key = static::get_primary_key();
    $timer = new Timer("$table GET all");
    $db = inventory_db_connect();
    $sql = static::get_sql($options);

    if (isset($options['order_by'])) {
      $dir = isset($options['dir']) ? strtoupper($options['dir']) : 'ASC';
      $sql .= ' ORDER BY ';
      $sql .= self::clean_key($options['order_by']);
      $sql .= " $dir";
    }

    $data = array();

    if ( $result = mysqli_query($db, $sql) ) {
      $num_rows = $result->num_rows;

      while($row = mysqli_fetch_array($result, MYSQLI_ASSOC) ) {
        // if ( !isset($row['recid']) ) {
        //   $row['RecordID'] = $row[$primary_key];
        // }
        if (isset($options['filter'])) {
          $filtered_row = array();
          foreach ($options['filter'] as $col) {
            $filtered_row[$col] = $row[$col];
          }
          $row = $filtered_row;
        }

        if (isset($options['flat'])) {
          array_push($data, $row);
        } else {
          $data[$row[$primary_key]] = $row;
        }
      }

      $responds = $data;
      mysqli_free_result($result);
    } else {
      //sql error
      Timer::log_error(mysqli_error($db), get_called_class());
      $responds = array();
    }
    //close connection
    inventory_db_close($db);
    $timer->end();

    // pagination
    if (isset($options['current_page']) && is_numeric($options['current_page'])) {
      $current_page = (int) $options['current_page'];
      $rows_per_page = isset($options['results_per_page']) ? (int) $options['results_per_page'] : 10;
      $total_pages = ceil($num_rows / $rows_per_page);
      $last_index = $num_rows - 1;

      if ($current_page > $total_pages) {
        $current_page = $total_pages;
      }

      if ($current_page < 1) {
        $current_page = 1;
      }

      $start = ($current_page - 1) * $rows_per_page;

      // if starting index is higher than the last index of the record set
      if ( $start > ($last_index - $rows_per_page) ) {
        // set the starting index to last index minus rows per page
        $start = $last_index - $rows_per_page;
      }

      // send responds
      return array_slice($responds, $start, $rows_per_page);
    }

    // send responds
    return $responds;
  }

  static function create($data=null) {
    if (method_exists(get_called_class(), 'before_create')) {
      $data = static::before_create($data);
    }
    //define variables
    $table = static::get_tablename();
    $timer = new Timer("$table CREATE");
    $db = inventory_db_connect();
    $fields = static::get_creatable_fields();
    $found_values = false;
    $sql_columns = "";
    $sql_values = "";
    //create columns and values for query
    foreach ($fields as $index => $key) {
      if ( isset($data[$key]) && strlen($data[$key])!=0 ) {
        $found_values = true;
        $clean_value = self::clean_value($data[$key], $db);
        $key = self::clean_key($key);
        $sql_columns .= "$key, ";
        $sql_values .= "$clean_value, ";
      }
    }

    if ( in_array('UnixTimestamp', $fields) ) {
      $time = time();
      $sql_columns .= "UnixTimestamp, ";
      $sql_values .= "$time, ";
    }

    if( $found_values ) {
      //make query
      $sql_columns = substr($sql_columns, 0, -2);
      $sql_values = substr($sql_values, 0, -2);
      $sql = "INSERT INTO $table ($sql_columns) VALUES ($sql_values)";

      // echo $sql . "\n\n";

      if( $result = mysqli_query($db, $sql) ) {
        //close connection and send responds
        $new_id = mysqli_insert_id($db);
        inventory_db_close($db);
        $timer->end();
        return new static($new_id, null, ['admin' => true]);
      } else {
        Timer::log_error(mysqli_error($db), get_called_class());
      }
    }
    //close connection and send responds
    // echo "\nCreate failed: [$sql]\n";
    inventory_db_close($db);
    $timer->end();
    return false;
  }

  public function update($data=null) {
    if (method_exists($this, 'before_update')) {
      $data = $this->before_update($data);
    }

    $table = static::get_tablename();
    $primary_key = static::get_primary_key();
    $timer = new Timer("$table UPDATE");
    $db = inventory_db_connect();
    $fields = static::get_updatable_fields();
    $found_values = false;
    $sql_set = "";
    //create columns and values for query
    foreach ($fields as $key) {
      if (isset($data[$key]) && ($data[$key] != $this->data[$key]) ) {
        $found_values = true;
        $clean_value = self::clean_value($data[$key], $db);
        $key = self::clean_key($key);
        $sql_set .= "{$key}=$clean_value, ";
      }
    }

    // update 'UpdatedUnixTimestamp if exists in table'
    if ( in_array('UpdatedUnixTimestamp', $fields) ) {
      $time = time();
      $sql_set .= "UpdatedUnixTimestamp = $time, ";
    }

    if( $found_values ) {
      //make query
      $sql_set = substr($sql_set, 0, -2);
      $sql = "UPDATE `$table` SET $sql_set WHERE `$primary_key`='$this->id'";

      if( $result = mysqli_query($db, $sql)) {
        $responds = true;
      } else {
        Timer::log_error(mysqli_error($db), get_called_class());
        // echo(mysqli_error($db));
        $responds = false;
      }
    } else {
      //no updatable fields found
      $responds = true;
    }
    //close connection and send responds
    inventory_db_close($db);
    $timer->end();
    return $responds;
  }

  function delete() {

    if (method_exists($this, 'before_delete')) {
      $this->before_delete();
    }

    $table = static::get_tablename();
    $timer = new Timer("$table DELETE");
    $primary_key = static::get_primary_key();
    $db = inventory_db_connect();
    $sql = "DELETE FROM `$table` WHERE `$primary_key`='$this->id'";
    //make query
    if( $result = mysqli_query($db, $sql) ) {
      $responds = true;
      $this->data = null;
    } else {
      Timer::log_error(mysqli_error($db), get_called_class());
      // echo(mysqli_error($db));
      $responds = false;
    }
    //close connection and send responds
    inventory_db_close($db);
    $timer->end();
    return $responds;
  }

  static function default_get_updatable_fields() {
    $db = inventory_db_connect();
    $table = static::get_tablename();
    $primary_key = static::get_primary_key();
    $db_table = DB_NAME;
    $sql = "SELECT DISTINCT(column_name) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='$table' AND table_schema = '$db_table'";
    $data = array();
    //make query
    if( $result = mysqli_query($db, $sql) ) {
      // $responds = mysqli_fetch_row($result);  //ignores id field
      while($row = mysqli_fetch_row($result) ) {
        if($row[0]==$primary_key) continue;
        $data[] = $row[0];
      }
      $responds = $data;
      mysqli_free_result($result);
    } else {
      echo(mysqli_error($db));
      $responds = false;
    }
    //close connection and send responds
    inventory_db_close($db);
    return $responds;
  }

  static function custom_list($sql)
  {
    $table = static::get_tablename();
    $timer = new Timer("$table GET custom list");
    $db = inventory_db_connect();
    $data = array();
    //make query
    if( $result = mysqli_query($db, $sql) ) {
      while($row = mysqli_fetch_array($result, MYSQLI_ASSOC) ) {
        $data[] = $row;
      }
      $responds = $data;
      mysqli_free_result($result);
    } else {
      //sql error
      Timer::log_error(mysqli_error($db), get_called_class());
      $responds = array();
    }
    //close connection and send responds
    inventory_db_close($db);
    $timer->end();
    return $responds;
  }

  static function custom_manipulate($sql) {
    // return $sql;
    $table = static::get_tablename();
    $timer = new Timer("Custom manipulate on $table");
    $db = inventory_db_connect();

    if ( !$result = mysqli_query($db, $sql) ) {
      Timer::log_error(mysqli_error($db), get_called_class());
    }

    inventory_db_close($db);
    $timer->end();
    return $result;
  }

  static function custom_single($sql)
  {
    $table = static::get_tablename();
    $timer = new Timer("$table GET custom single");
    $db = inventory_db_connect();
    $data = array();
    //make query
    if ( $result = mysqli_query($db, $sql) ) {
      $row = mysqli_fetch_assoc($result);
      $responds = $row;
      mysqli_free_result($result);
    } else {
      //sql error
      Timer::log_error(mysqli_error($db), get_called_class());
      $responds = array();
    }
    //close connection and send responds
    inventory_db_close($db);
    $timer->end();
    return $responds;
  }

  static function custom_get_by($sql)
  {
    $db = inventory_db_connect();
    $primary_key = static::get_primary_key();
    //make query
    if ($result = mysqli_query($db, $sql) ) {
      if (mysqli_num_rows($result) > 0) {
        $responds = mysqli_fetch_array($result, MYSQLI_ASSOC);
        inventory_db_close($db);
        return new static($responds[$primary_key]);
      }
      mysqli_free_result($result);
    } else {
      // sql error
      Timer::log_error(mysqli_error($db), get_called_class());
    }
    //close connection
    inventory_db_close($db);
    return false;
  }

  static function get_creatable_fields() {
    if(method_exists(get_called_class(), 'custom_get_creatable_fields')) {
      return static::custom_get_creatable_fields();
    } else {
      return static::default_get_updatable_fields();
    }
  }

  static function get_updatable_fields() {
    if(method_exists(get_called_class(), 'custom_get_updatable_fields')) {
      return static::custom_get_updatable_fields();
    } else {
      return static::default_get_updatable_fields();
    }
  }

  protected static function get_sql($options = array()) {
    if(method_exists(get_called_class(), 'custom_get_sql')) {
      return static::custom_get_sql($options);
    } else {
      $table = static::get_tablename();
      return "SELECT * FROM `$table` as o";
    }
  }

  protected static function get_one_sql($id, $options = array()) {
    if(method_exists(get_called_class(), 'custom_get_one_sql')) {
      return static::custom_get_one_sql($id, $options);
    } else {
      $primary_key = static::get_primary_key();
      $sql = static::get_sql() . " WHERE o.`$primary_key`='%u'";
      return sprintf($sql, $id);
    }
  }

  public static function clean_key($key) {
    $parts = explode('.', $key);
    $parts = array_map(function($el) {
      return preg_replace('/`/', "\\`", $el);
    }, $parts);
    return '`' . implode('`.`', $parts) . '`';
  }

  public static function clean_value($value, $db = false) {
    if (!$db) {
      $db = inventory_db_connect();
    }
    if ((string) ((int) $value) == $value && ((int) $value == 0 || (int) $value == 1)) {
      return sprintf('%d', $value);
    } elseif ((string) $value == '' || (string) $value == 'NULL') {
      return 'NULL';
    } elseif ((string) $value == 'NOW()') {
      return 'NOW()';
    } else {
      return "'" . mysqli_real_escape_string($db, $value) . "'";
    }
  }

  public static function percentage_two_dec($a=null, $b=null) {
    return number_format(bcdiv($a, $b, 4) * 100, 2);
  }

  public static function contains_string($haystack, $needle) {
    return strpos($haystack, $needle) !== false;
  }
}
?>
