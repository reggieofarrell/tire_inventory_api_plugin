<?php

  require_once('morph.php');

  spl_autoload_register(function($class) {
    $file = MD . '/md_controllers/' . Morph::camel_to_snake($class) . '.php';
    if (file_exists($file)) {
      include_once $file;
    } else {
      return false;
    }
  });

  spl_autoload_register(function($class) {
    $file = MD . '/records/' . $class . '.php';
    if (file_exists($file)) {
      include_once $file;
    } else {
      return false;
    }
  });

  spl_autoload_register(function($class) {
    $file = MD . '/md/' . $class . '.php';
    if (file_exists($file)) {
      include_once $file;
    } else {
      return false;
    }
  });

?>
