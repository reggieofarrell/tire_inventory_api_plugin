<?php
  if ( !defined( 'ABSPATH' ) ) {
    exit;
  }

  // custom routes
  Router::ns_route('/api/', [
    'GET@User/me' => 'User#me',
    'POST@User/change_password' => 'User#change_password',
    'POST@User/check_password' => 'User#check_password',
    'POST@Avails/with_options' => 'Avails#read_all',
    'GET@TestEmail' => 'User#test_email'
  ]);

  
  Router::resource('/api/', 'User');
  Router::resource('/api/', 'Suppliers');
  Router::resource('/api/', 'TireBrands');
  Router::resource('/api/', 'TireModels');
  Router::resource('/api/', 'TirePatterns');
  Router::resource('/api/', 'TireSizes');
  Router::resource('/api/', 'TraCodes');
  Router::resource('/api/', 'PlyRatings');
  Router::resource('/api/', 'FobLocations');
  Router::resource('/api/', 'Avails');
  Router::resource('/api/', 'VendorRating');
  Router::resource('/api/', 'Role');
  Router::resource('/api/', 'RubberCompounds');

?>
