<?php
$routes = array(
  'AirlineController' => array(
    'create_organization' => 'create',
  ),
  'PlaneController' => array(
    'planes' => 'plane',
    'plane/add' => 'add',
    'plane/edit/([0-9]+)' => 'edit/$1',
    'plane/delete' => 'delete',
  ),
  'WorkerController' => array(
    'workers' => 'worker',
    'worker/add' => 'add',
    'worker/edit/([0-9]+)' => 'edit/$1',
    'worker/delete' => 'delete',
  ),
  'PassengerController' => array(
    'my_tickets' => 'myTickets',
    'history' => 'history',
    'my_requests' => 'myRequests',
    'flight_charter_info/([a-zA-Z0-9]+)' => 'flightCharterInfo/$1'
  ),
  'UserController' => array(
    'profile' => 'profile',
    'logout' => 'logout',
    'change_password' => 'changePassword',
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
     'ticket/([a-zA-Z0-9\-]+)' => 'ticketInfo/$1',
      '' => 'index'
    )
);