<?php
require_once 'Record.php';

/**
 *  To make a new CRUD object follow this template
 */
class Example extends Record
{
  protected static $tableName = "example_tbl";
  protected static $primaryKey = "exampleID";

  /**
  *  The above code is all you need to make a new object for the "example_tbl"
  *
  *  $all_examples = Example::get();  Returns an array of every Example->data array
  *  $one_example = Example::get($id); Returns a new object Example
  *  $one_example->id primary key for that record
  *  $one_example->data Aarray of all the data in the table for that id
  *  $one_example->update($new_data_array);
  *  $one_example->delete();
  *  $one_example = Example->create($new_data_array);
  */

  /*
  ****************************************************************************
  *            CUSTOM FUNCTIONS FOR EXTRA FUNTIONALITY
  *  to call these functions in the object, don't use their custom_ prefix
  *  for example this->my_fucntion_name();
  ****************************************************************************
  */

  /*
  * Add this function if you would like a custom sql for selecting your records
  * for example, aliasing any fields names, or joining additional tables
  * ! if you want to add a where clase to this funtion you must also make a custom_get_one_sql function
  * @return string
  */
  static function custom_get_sql() {
    # this is the default functionality
    $table = static::get_tablename();
    return "select * from $table";
  }

  /*
  * Add this function if you would like a custom sql for selecting one of your records
  * for example, aliasing any fields names, or joining additional tables
  * @param the primary key value you want to look up your record by
  * @return string
  */
  static function custom_get_one_sql($id) {
    # this is the default functionality
    $primary_key = static::get_primary_key();
    return static::get_sql() . " WHERE o.`$primary_key`='$id'";
  }

  /*
  * This is an example of a custom function for a list of objects
  * name this function whatever you want
  * @return array of array
  */
  public function what_ever_you_want()
  {
    $sql = "Make a custom sql query for grabbing your data";
    return static::custom_list($sql);
  }

  /*
  * Add this function if you would like a list of fields that can be created from this object
  * by default calls get_updatable_fields()
  * @return array with no keys, only values
  */
  static function custom_get_creatable_fields() {
    # use this functionality to get a list of all field in the table
    return self::default_get_updatable_fields();
  }
}

?>
