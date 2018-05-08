<?php

class TireBrandsCtrl extends Ctrl {

  public function read_all($params = []) {
    if ($this->signed_in()) {
      $records = TireBrands::get(null, ['flat' => true]);
      return array('json' => $records);
    }
    return array('error' => ':auth');
  }

  public function read_one($params = []) {
    if ($this->signed_in()) {
      $record = TireBrands::get($params['id']);
      return array('json' => array($record->data));
    }
    return array('error' => ':auth');
  }

  public function update_one($params = []) {
    global $_BODY;

    if ($this->signed_in()) {
      $record = TireBrands::get($params['id']);
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
      $supplier = TireBrands::create($_BODY);
      if ( !$supplier->id ) {
        return array('json' => ['error' => 'create failed', 'body' => $_BODY]);
      }
      return array('json' => $supplier->data);
    }
    return array('error' => ':auth');
  }

  public function delete_one($params = []) {
    if ($this->signed_in()) {
      $record = TireBrands::get($params['id']);
      if ($record->delete()) {
        return ['json' => ['deleted' => true]];
      }
      return array('error' => ':server');
    }
    return array('error' => ':auth');
  }
}

?>
