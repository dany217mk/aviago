<?php
$routes = array(
  'UserController' => array(
    'profile' => 'profile',
  ),
  'MainController' => array(
    'auth' => 'auth',
     'report/([0-9]+)' => 'report/$1',
     'charter_request' => 'charterRequest',
     'check_in' => 'checkIn',
     'flight_board' => 'flightBoard',
      '' => 'index'
    )
);