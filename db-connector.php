<?php
  function inventory_db_connect()
  {
    $mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if(!$mysqli) {
      die('Could not connect: ' . mysqli_error($mysqli) . "\n" );
    }
    return $mysqli;
  }

  function inventory_db_close($mysqli)
  {
    mysqli_close($mysqli);
  }
?>
