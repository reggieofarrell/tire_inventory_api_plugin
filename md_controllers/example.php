<?php

  class ExampleCtrl extends Ctrl {

    public function read_all($params = []) {
      return array('json' => array());
    }

    public function read_one($params = []) {
      return array('json' => array());
    }

    public function update_one($params = []) {
      global $_BODY;
      return array('json' => array());
    }

    public function create_one($params = []) {
      global $_BODY;
      return array('json' => array());
    }

    public function delete_one($params = []) {
      return array('json' => array());
    }

  }

?>
