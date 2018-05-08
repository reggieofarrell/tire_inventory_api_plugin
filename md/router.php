<?php // Routing service


  include_once(MD . '/md/autoloader.php');
  include_once(MD . '/routes.php');
  include_once(MD . '/md/ctrl.php');
  include_once(MD . '/md/morph.php');
  include_once(MD . '/md/encode.php');
  include_once(MD . '/md/mdemail.php');

  $_BODY;

  class Router {
    protected static $current_request;
    public static $routes;
    public static $current_route_template;

    // aggregate route configurations
    // parse and set Post/Put data if provided
    public static function initialize() {
      global $_BODY;

      // Format PUT/POST data as an associative array
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_BODY = json_decode(file_get_contents('php://input'), TRUE);
      }
    }

    public static function redirect($path, $preserve_path = false) {
      $base = site_url();
      $dest = '';
      if ($preserve_path) {
        $dest = "?redirect_to=" . urlencode($_SERVER['REQUEST_URI']);
      }
      header("Location: $base$path$dest");
      exit;
    }

    // matches method and request with first route template
    public static function resolve($method, $original_req) {
      global $_BODY;

      $parts = explode('?', $original_req);
      $uri = urldecode(Morph::trim_trailing_slash($parts[0]));
      self::$current_request = $method . '/' . implode('/', explode('/', substr($uri, 1)));
      $request_segments = explode('/', self::$current_request);

      parse_str($parts[1], $args);
      $args['URI'] = $uri;

      return self::find_route($request_segments, $args);
    }

    private static function find_route($request_segments, $arguments = []) {
      foreach (self::$routes as $rt => $ctrl) {
        $args = $arguments;
        $route_segments = explode('/', $rt);
        $count = 0;
        // concurrently loop through route and uri segments
        if (count($request_segments) === count($route_segments)) {
          foreach ($route_segments as $rt_seg) {
            $length = count($route_segments);
            if (isset($request_segments[$count]) && ($rt_seg === $request_segments[$count] || (isset($rt_seg[0]) && $rt_seg[0] === ':'))) {
              // assign templated key=>value pairs
              if (isset($rt_seg[0]) && $rt_seg[0] === ':') {
                $key = substr($rt_seg, 1);
                $value = $request_segments[$count];
                $args[$key] = $value;
              }
              $count += 1;
              // resolve with template and key=>values if template successfully processes uri
              if ($count === $length) {
                $parts = explode('#', $ctrl);
                array_shift($route_segments);
                $route_id = implode('/', $route_segments);
                Router::$current_route_template = $route_id;
                return ['ctrl' => $parts[0] . 'Ctrl', 'method' => $parts[1], 'args' => $args, 'route_template' => $route_id];
              }
            // exit route if template match failed
            } else {
              break;
            }
          }
        }
      }
      return FALSE;
    }

    public static function is_api_request() {
      return strpos($_SERVER['REQUEST_URI'], SUBDIR . '/api/') === 0;
    }

    // route list getter
    public static function get_routes() {
      return self::$routes;
    }

    // formatted request getter
    public static function get_request() {
      return self::$current_request;
    }

    // namespace definition for explicit routes
    public static function ns_route($namespace, $routes) {
      foreach ($routes as $route => $resolution) {
        $route_parts = explode('@', $route);
        if ($route_parts[1][0] === '/') {
          throw new Exception("Custom routes must NOT start with a `/` : GET@my_route/testing", 1);
        }
        $methods = explode('|', $route_parts[0]);
        foreach ($methods as $method) {
          self::$routes["$method$namespace{$route_parts[1]}"] = $resolution;
        }
      }
    }

    // resource definition for predefined routes
    public static function resource($namespace, $model) {
      $class = strtoupper($model[0]) . substr($model, 1);
      self::ns_route($namespace, [
        "GET@$model" => "$class#read_all",
        "GET@$model/:id" => "$class#read_one",
        "POST@$model/:id" => "$class#update_one",
        "POST@$model" => "$class#create_one",
        "DELETE@$model/:id" => "$class#delete_one"
      ]);
    }

  }

?>
