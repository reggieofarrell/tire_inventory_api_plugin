<?php

class UserCtrl extends Ctrl {

  public function read_all($params = []) {
    if ($this->user_is_admin()) {
      $records = User::get(null, ['flat' => true]);
      return array('json' => $records);
    }
    return array('error' => ':auth');
  }

  public function read_one($params = []) {
    if ($this->user_is_admin() || $this->user->id == $params['id']) {
      $record = User::get($params['id']);
      return array('json' => $record->data);
    }
    return array('error' => ':auth');
  }

  public function update_one($params = []) {
    global $_BODY;

    if ($this->user_is_admin() || $this->user->id == $params['id']) {
      $user_meta_id = UserMeta::get_by_user_id($params['id'])['UserMetaID'];

      if ( !$user_meta = UserMeta::get($user_meta_id)->update($_BODY) ) {
        return array( 'json' => ['error' => 'Problem updating user meta']);
      }
      return $this->read_one($params['id']);
    }
    return array('error' => ':auth');
  }

  public function create_one($params = []) {
    global $_BODY;

    if ($this->user_is_admin()) {
      $required = array('Email', 'Password');
      $missing = self::check_required_keys($required, $_BODY);
      if ( count($missing) > 0 ) return array('json' => ['error' => 'Missing required fields']);

      if (!filter_var($_BODY['Email'], FILTER_VALIDATE_EMAIL)) {
        return array('json' => ['error' => 'Invalid email address']);
      }

      if ( !(strlen($_BODY['Password']) > 7) ) {
        return array('json' => ['error' => 'Password must be at least 8 characters long']);
      }

      // $password = wp_generate_password();
      $wp_user = wp_create_user($_BODY['Email'], $_BODY['Password'], $_BODY['Email']);
      if (isset($wp_user->errors)) return array('json' =>['error' => $wp_user->errors]);
      if (!$wp_user) return array('json' => ['error' => 'wp_user']);

      $_BODY["UserID"] = $wp_user;

      $user_meta = UserMeta::create($_BODY);
      if (!$user_meta->id) {
        wp_delete_user($wp_user);
        return array('json' => ['error' => 'Problem creating user meta record', 'problem' => $user_meta]);
      }

      MDEmail::send_render(null, 'new_account', [
        'email'      => $_BODY['Email'],
        'password'   => $_BODY['Password']
      ]);

      // wp_mail($_BODY['Email'], 'New Tire Inventory Account', 'test');

      return array( 'json' => ['success' => $this->read_one(['id' => $wp_user])] );
    }
    return array('error' => ':auth');
  }

  public function test_email() {
    MDEmail::send_render(null, 'new_account', [
      'email'      => 'reggie@ofarrellaudio.com',
      'password'   => 'nope'
    ]);
  }

  public function delete_one($params = []) {
    if ($this->user_is_admin()) {
      include($_SERVER['DOCUMENT_ROOT'] . "/wp-admin/includes/user.php" );
      if ( !wp_delete_user($params['id']) ) {
        return array('json' => ['error' => 'wp_delete_user']);
      }

      $user_meta_id = UserMeta::get_by_user_id($params['id'])['UserMetaID'];
      if ( UserMeta::get($user_meta_id)->delete() ) {
        return array('json' => ['deleted' => true] );
      }

      return array('json' => ['error' => 'Problem deleting UserMeta']);
    }
    return array('error' => ':auth');
  }

  public function me($params = []) {
    if ($this->signed_in()) {
      $record = $this->user;
      return array('json' => $record->data);
    }
    return array('error' => ':auth');
  }

  public function change_password($params = []) {
    global $_BODY;

    if ($this->signed_in()) {
      if ( !(strlen($_BODY['Password']) > 7) ) {
        return array('json' => ['error' => 'Password must be at least 8 characters long']);
      }

      if ( isset($_BODY['Password'])
        && User::is_correct_password($_BODY['CurrentPassword'])
        && $_BODY['Password'] === $_BODY['VerifyPassword'] ) {
          $user_id = wp_update_user(array(
            'user_pass' => $_BODY['Password'],
            'ID' => get_current_user_id()
          ));
          if ($user_id) {
            return array('json' => array('success' => true));
          }
          return array('json' => ['error' => 'problem updating user']);
      }
      return array('error' => 'incorrect password');
    }
    return array('error' => ':auth');
  }

  public function check_password($params = []) {
    global $_BODY;

    if ($this->signed_in()) {
      if ( User::is_correct_password($_BODY['CurrentPassword']) ) {
        return array('json' => ['success' => true]);
      }

      return array('json' => ['error' => 'Incorrect password']);
    }
    return array('error' => ':auth');
  }

}

?>
