<?php // Route resolver

  include_once $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php';

  if (INVENTORY_ENV == 'dev') {
    header("Access-Control-Allow-Origin: http://inventory-local:8080");
    header("Access-Control-Allow-Credentials: true");
  }

  ini_set('memory_limit', '4096M');

  define('MD', ABSPATH . 'wp-content/plugins/inventory-wp-plugin');

  include_once(MD . '/vendor/autoload.php');
  include_once(MD . '/md/router.php');


  Router::initialize();
  Ctrl::set_user(User::get_logged_in_user());
  $parsed_request = Router::resolve($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

  ob_start();
  if ($parsed_request) {
    Ctrl::process($parsed_request);
  } else {
    http_response_code(404);
    if (INVENTORY_ENV == 'production') {
      echo 'not found';
    } else {
      echo json_encode(array(
        'error' => 'not_found',
        '$routes' => Router::$routes
      ));
    }
    ob_flush();
  }

?>
