<?php

class RoleCtrl extends Ctrl {

  public function read_all($params = []) {
    if ($this->user_is_admin()) {
      $records = Role::get(null, ['flat' => true]);
      return array('json' => $records);
    }
    return array('error' => ':auth');
  }

}

?>
