<?php

  class Morph {

    public static function slashes($date_str) {
      return str_replace('-', '/', $date_str);
    }

    public static function is_empty($str) {
      return empty($str) || ($str == "\x00");
    }

    public static function snake_to_camel($str) {
      return preg_replace_callback('/-([a-z])/', function($matches) {
        return strtoupper($matches[1]);
      }, $str);
    }

    public static function camel_to_snake($str) {
      return strtolower(preg_replace_callback('/([a-z])([A-Z])/', function($matches) {
        return $matches[1] . '_' . strtolower($matches[2]);
      }, $str));
    }

    public static function array_pluck($array, $key) {
      return array_map(function($el) use ($key) {
        return $el[$key];
      }, $array);
    }

    public static function pick($array, $keys, $new_keys = null) {
      return array_map(function($el) use ($keys, $new_keys) {
        $filtered = array();
        foreach ($keys as $idx => $key) {
          if ($new_keys) {
            $filtered[$new_keys[$idx]] = $el[$key];
          } else {
            $filtered[$key] = $el[$key];
          }
        }
        return $filtered;
      }, $array);
    }

    public static function pick_as($array, $keys, $new_keys) {
      return self::pick($array, $keys, $new_keys);
    }

    public static function filter_values($array, $callback) {
      return array_values(array_filter($array, $callback));
    }

    public static function array_map($mapper, $array) {
      $mapped_array = [];
      foreach ($array as $key => $value) {
        $item = $mapper($value);
        if (is_array($item)) {
          $mapped_array += $item;
        } else {
          $mapped_array[] = $item;
        }
      }
      return $mapped_array;
    }

    public static function array_to_string($arr, $limit = 99999) {
      function to_string($arr) {
        $values = [];
        foreach ($arr as $k => $v) {
          if (is_array($v)) {
            $v = to_string($v);
          }
          if (is_string($v)) {
            $v = htmlspecialchars($v);
          }
          $v = Database::format($v);
          $values[] = "<b><i>$k</i></b>: $v";
        }
        return '{' . implode(', ', $values) . '}';
      }
      return to_string(array_slice($arr, 0, $limit));
    }

    public static function sort_by($prop) {
      return function($a, $b) use ($prop) {
        return $a[$prop] - $b[$prop];
      };
    }

    public static function trim_trailing_slash($uri) {
      $uri = preg_replace('/\/\/+/', '/', $uri); // convert multiple slashes to single slashes
      $uri_length = strlen($uri);
      if (substr($uri, $uri_length - 1, 1) === '/') {
        return substr($uri, 0, $uri_length - 1);
      } else {
        return $uri;
      }
    }

    public static function controller_model($controller) {
      return substr($controller, 0, strlen($controller) - 4);
    }

    public static function strip_newline($str) {
      return trim(preg_replace("/\n|\r|\s\s+/", ' ', $str));
    }

    public static function isAssoc($arr) {
      if (array() === $arr) return false;
      return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public static function format_date($str, $format = 'M d, Y') {
      $date = new DateTime($str);
      return $date->format($format);
    }

    public static function time_ago($date_str) {
      $time = time() - strtotime($date_str);
      $time = ($time<1) ? 1 : $time;
      $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
      );

      foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
      }
    }

  }

?>
