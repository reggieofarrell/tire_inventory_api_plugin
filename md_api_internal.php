<?php

  include_once $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php';
  define('MD', ABSPATH . 'wp-content/plugins/inventory-wp-plugin');

  include_once(MD . '/md/router.php');

  Router::initialize();
  Ctrl::set_user(User::get_logged_in_user());

?>
