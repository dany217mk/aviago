<?php
$routes = array(
  'AirlineController' => array(
    'create_organization' => 'create',
  ),
  'WorkerController' => array(
    'workers' => 'worker',
    'worker/add' => 'add',
    'worker/edit/([0-9]+)' => 'edit/$1',
    'worker/delete' => 'delete',
  ),
  'UserController' => array(
    'profile' => 'profile',
    'logout' => 'logout',
    'change_password' => 'changePassword'
  ),
  'MainController' => array(
    'auth' => 'auth',
     'report/([0-9]+)' => 'report/$1',
     'charter_request' => 'charterRequest',
     'charter_check' => 'charterCheck',
     'charter_number' => 'charterNumber',
     'check_in' => 'checkIn',
     'flight_board' => 'flightBoard',
     'flight_number' => 'flightNumber',
     'book_flight/([a-zA-Z0-9]+)' => 'bookFlight/$1',
      '' => 'index'
    )
);