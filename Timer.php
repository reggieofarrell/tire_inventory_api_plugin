<?php
date_default_timezone_set('America/Chicago');
/**
 *
 */
class Timer
{
  protected $file = __DIR__ . "/timer_log.txt";
  protected $tag;
  protected $start_time;

  function __construct($tag)
  {
    $this->start_time = self::microtime_float();
    $this->tag = $tag;
  }

  function end()
  {
    $end = self::microtime_float();
    $time_taken = $end - $this->start_time;
    $log_message = date('m/j/y :: h:i:s') . " :: TIMER LOG :: " . $this->tag . " :: " . $time_taken . "sec \n";
    file_put_contents($this->file, $log_message, FILE_APPEND | LOCK_EX);
  }

  static function microtime_float()
  {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
  }

  public static function log_error($err, $called_class)
  {
    $log_message = date('m/j/y :: h:i:s') . " :: ERROR from $called_class \n $err :: \n\n";
    $file = __DIR__ . '/sql_error_log.txt';
    file_put_contents($file, $log_message, FILE_APPEND | LOCK_EX);
    if (inventory_ENV == 'dev') echo "MySQL ERROR: " . $err . "\n";
  }
}

 ?>
