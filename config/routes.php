<?php
$routes = array(
  'AirlineController' => array(
    'create_organization' => 'create',
    'airline_charters' => 'airlineCharters',
    'airline_charter/([a-zA-Z0-9\-]+)' => 'airlineCharter/$1',
    'airline_flights' => 'airlineFlights',
    'add_airline_to_charter_request/([0-9]+)' => 'addAirlineToCharter/$1',
    'flight_archive/([a-zA-Z0-9]+)' => 'flightArchive/$1',
    'airline_flight/([a-zA-Z0-9\-]+)' => 'airlineFlight/$1',
    'flight_passengers/([a-zA-Z0-9]+)' => 'flightPassengers/$1',
  ),
  'CrewController' => array(
    'my_schedule' => 'mySchedule',
    'my_flight_history' => 'myFlightHistory',
    'crew_flight/([a-zA-Z0-9]+)' => 'crewFlight/$1',
    'crew_delete/([0-9]+)' => 'crewDelete/$1'
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