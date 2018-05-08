<?php
  include_once(ABSPATH . 'wp-content/plugins/inventory-wp-plugin/vendor/autoload.php');

  class MDEmail {
    static $dev_email = '/reggie@ofarrellaudio.com/';

    public static function send($email, $subject, $body, $headers = [], $attachment = false) {
      if (!is_array($headers)) {
        $headers = [$headers];
      }
      array_unshift($headers, "Content-type: text/html;charset=utf-8");
      array_push($headers, "From: Tire Inventory <info@inventorytire.com>");
      array_push($headers, "Reply-To: info@inventorytire.com");

      if (INVENTORY_ENV == 'production') {
        wp_mail($email, $subject, $body, $headers, $attachment);
        return true;
      } else {
        if (preg_match(self::$dev_email, $email)) {
          wp_mail($email, "TESTING: $subject", $body, $headers);
          return true;
        }
      }
      return false;
    }

    public static function compile_email_template($template_name, $options = []) {
      $options['base_url'] = site_url();

      $dust = new \Dust\Dust();
      $default_template = file_get_contents(MD . "/email_templates/_default.dust");
      $file_template = file_get_contents(MD . "/email_templates/$template_name.dust");
      if(!isset($options['email_subject'])) {
        preg_match('/<!--\s*?Subject:\s*?([^-]+)\s*?-->/m', $file_template, $matches);
        $subject = $matches[1];
        if (empty($subject)) {
          die('Email template requires subject comment: &lt;!-- Subject: &lt;subject line&gt; --&gt;');
        }
      } else {
        $subject = $options['email_subject'];
      }

      $dust->compile($default_template, 'default');
      $template = $dust->compile($file_template);


      return [
        'html' => $dust->renderTemplate($template, $options),
        'subject' => $subject
      ];

    }

    public static function send_render($user, $template_name, $options, $attachment = false, $return = false) {
      if (!is_array($options)) {
        $options = [];
      }

      if ($user) {
        $options['name'] = $user->data['FirstName'];
      }

      $headers = [];
      if (isset($options['cc'])) {
        if (!is_array($options['cc'])) {
          $options['cc'] = [$options['cc']];
        }
        foreach ($options['cc'] as $cc) {
          array_push($headers, "cc: $cc");
        }
      }

      $output = static::compile_email_template($template_name, $options);

      if ($return) {
        return $output['html'];
      } else {
        if ($user) {
          self::send($user->data['Email'], $output['subject'], $output['html'], $headers, $attachment);
        } else {
          self::send($options['email'], $output['subject'], $output['html'], $headers, $attachment);
        }
      }
    }

  }



?>
