<?php

class AvailsCtrl extends Ctrl {

  public function read_all($params = []) {
    global $_BODY;

    if ($this->signed_in()) {
      $options = ['flat' => true];
      if (!empty($_BODY)) {
        $options = array_merge($options, $_BODY);
      }

      $records = Avails::get(null, $options);
      return array('json' => $records);
    }
    return array('error' => ':auth');
  }

  public function read_one($params = []) {
    if ($this->signed_in()) {
      $record = Avails::get($params['id']);
      return array('json' => array($record->data));
    }
    return array('error' => ':auth');
  }

  public function update_one($params = []) {
    global $_BODY;
    if ($this->signed_in()) {
      $record = Avails::get($params['id']);
      if ($record->update($_BODY)) {
        return [ 'json' => ['success' => true, $this->read_one($params['id'])] ];
      }
      return array('error' => ':server');
    }
    return array('error' => ':auth');
  }

  public function create_one($params = []) {
    global $_BODY;

    if ($this->signed_in()) {
      $supplier = Avails::create($_BODY);
      if ( !$supplier->id ) {
        return array('error' => ':server');
      }
      return array('json' => $supplier->data);
    }
    return array('error' => ':auth');
  }

  public function delete_one($params = []) {
    if ($this->signed_in()) {
      $record = Avails::get($params['id']);

      if ($record->delete()) {
        return ['json' => ['deleted' => true]];
      }
      return array('error' => ':server');
    }
    return array('error' => ':auth');
  }

  public function get_inventory($params = []) {
    $records = Avails::get_inventory();
    return array('json' => $records);
  }

}

?>
