<?php
  include_once(MD . '/md/encode.php');

  class Ctrl {
    private static $global_user;
    public $user;

    public static function set_user($user) {
      static::$global_user = $user;
    }
    public static function get_user() {
      return static::$global_user;
    }

    // bootstrap function for processing routing information (Ctrl#method)
    public static function process($parsed_request, $internal = false) {
      if (!$parsed_request) {
        self::reject('404');
      }

      // reject all requests if user is not logged in and env is production
      if (!get_current_user_id() && INVENTORY_ENV == 'production') {
        self::reject('auth');
      }

      if (!$internal) {
        header("X-INVENTORY-CTRL: {$parsed_request['ctrl']}#{$parsed_request['method']}");
      }

      // Instantiate the controller
      $ctrl = new $parsed_request['ctrl']();
      // Call the api-defined method on the controller object
      // passing any uri args set by "/:{var}/" in the api route template
      $ctrl->user = static::get_user();
      if (!$ctrl->user && get_current_user_id()) {
        die('please register a user getter with Ctrl::register_user before using the api');
      }
      $data_array = $ctrl->{$parsed_request['method']}($parsed_request['args']);
      if ($data_array) {
        foreach ($data_array as $type => $data) {
          if ($internal) {
            return $data;
          } else {
            return self::render($type, $data, $data_array);
          }
        }
      }
      return false;
    }

    public static function reject($type) {
      switch ($type) {
        case 'auth':
          return self::render('error', ':auth');
          break;
        case '404':
          return self::render('error', ':404');
          break;
      }
    }

    private static function render($type, $response = '', $options = array()) {
      $output = trim(Morph::strip_newline(ob_get_clean()));
      header("X-INVENTORY-LOG: $output");

      switch ($type) {
        case 'json':
          header('Content-Type: application/json');
          echo json($response);
          break;
        case 'text':
          header('Content-Type: text/plain');
          echo $response;
          break;
        case 'html':
          header('Content-Type: text/html');
          echo $response;
          break;
        case 'file':
          static::file($response);
          break;
        case 'error':
          switch ($response) {
            case ':auth':
              http_response_code(403);
              echo "not_authorized";
              break;
            case ':server':
              http_response_code(500);
              echo "server_error";
              break;
            case ':404':
              http_response_code(404);
              echo "not_found";
              break;
            default:
              http_response_code(500);
              echo $response;
              break;
          }
          if (isset($options['message'])) {
            echo $options['message'];
          }
      }
    }

    public static function file($response) {
      $file = fopen($response, 'r');
      $size = filesize($response);
      $type = mime_content_type($response);
      header('Content-Type: ' . $type);
      header('Content-Length: ' . $size);
      fpassthru($file);
      fclose($file);
      exit();
    }

    public function user_has_role($roles) {
      if ($this->user) {
        return $this->user->has_role($roles);
      }
      return false;
    }

    public function user_is_admin() {
      if ($this->user) {
        return $this->user->is_admin();
      }
      return false;
    }

    public function signed_in() {
      return isset($this->user);
    }

    public static function col_filter($target, $cols) {
      $rows = $target;
      $isAssoc = Morph::isAssoc($target);
      if ($isAssoc) {
        $rows = array($target);
      }
      $filtered = array();
      foreach ($rows as $item) {
        $row = array();
        foreach ($cols as $col) {
          $row[$col] = isset($item[$col]) ? $item[$col] : null;
        }
        array_push($filtered, $row);
      }
      if ($isAssoc) {
        $filtered = $filtered[0];
      }
      return $filtered;
    }

    public static function check_required_keys(array $required_keys, array $submission) {
      return array_diff_key(array_flip($required_keys), $submission);
    }

  }

?>
