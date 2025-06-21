<?php
class MainController
{


  public $helper;
  public $userModel;
  public function __construct(){
    $this->helper = new Helper();
    $this->userModel = new User();
  }

  public function actionIndex(){
    if ($_SERVER['REQUEST_URI'] != REQUEST_URI_EXIST) {
      header("Location: " . SITE_PAGE_NOT_FOUND);
      die();
    }
    $scripts = ['flights.js', 'notification.js'];
    $styles = [CSS . '/home.css'];
    $title  = SITE_NAME;
    $menu_active = 'home';


    $flightModel = new Flight();

    if (isset($_POST['departure'])){
      $dep = $_POST['departure'];
      $arr = $_POST['arrival'];
      $date = $_POST['date'];
      $requestedSeats = $_POST['passenger_counts'];
      $data = $flightModel->getFlightsWithSeats($dep, $arr, $date, $requestedSeats);
      $columns = ['Авиакомпания', 'Отправление', 'Прибытие', 'Самолет', 'Время вылета', 'Время прилёта', 'Билеты'];
    }


    $airportModel = new Airport();
    $airports = $airportModel->getAll();

    setlocale(LC_TIME, 'ru_RU.UTF-8'); 
    $dates = [];
    $startDate = new DateTime('today');

    for ($i = 0; $i <= 61; $i++) {
        $isoFormat = $startDate->format('Y-m-d'); 
        $prettyFormat = strftime('%e %B %Y', $startDate->getTimestamp()); 
        $prettyFormat = mb_convert_case(trim($prettyFormat), MB_CASE_TITLE, "UTF-8"); 

        $dates[] = [$isoFormat, $prettyFormat];
        $startDate->modify('+1 day');
    }

    $this->helper->outputCommonHead($title, $menu_active, $styles);
    require_once  './views/home.html';
    $this->helper->outputCommonFoot($scripts);
  }


  public function actionBookFlight($data){
    $flight_code = $data[0];

    $flightModel = new Flight();

    $flight = $flightModel->getFlightByNumber($flight_code);

    if (!$flight){
      header("Location: " . FULL_SITE_ROOT . "/report/527");
      exit;
    }
    $menu_active = '';

    if (isset($_POST['book_name1'])) {
      $flight_id = $flight['id'];
      $flightModel = new Flight();
      if (isset($_POST['charter_request_id'])){
        $charterRequestId = $_POST['charter_request_id'];
      } else{
        $charterRequestId = null;
      }
      $booking_number = $flightModel->bookFlightPassengers($flight_id, $_POST, $charterRequestId);

      setcookie('booking_number', $booking_number, time() + 1000, '/');
      header("Location: " . FULL_SITE_ROOT . "/report/986");
      exit;
  }
    

    if (isset($_COOKIE['uid'])){
      $user = $this->userModel->getUser();
      if ($user['access_level'] == 5){
        $userInfo = $this->userModel->getUserInfoById($user['id']);
      }
    }

    if (isset($_POST['book_flight_btn'])){
      $book_passenger_counts = $_POST['book_passenger_counts'];
      if (isset($_POST['book_charter_request_id'])){
        $book_charter_request_id = $_POST['book_charter_request_id'];
      }
    } else{
      header("Location: " . FULL_SITE_ROOT . "/report/404");
      exit;
    }

    $scripts = ['book_flight.js', 'notification.js'];
    $styles = [CSS . '/book_flight.css'];
    $title  = $flight['flight_number'];


    $this->helper->outputCommonHead($title, $menu_active, $styles);
    require_once  './views/book_flight.html';
    $this->helper->outputCommonFoot($scripts);
  }

  public function actionCharterNumber(){
    $scripts = ['flights.js', 'notification.js'];
    $styles = [CSS . '/home.css'];
    $title  = SITE_NAME;
    $menu_active = 'home';

    $flightModel = new Flight();
        
    if (isset($_POST['flight_code'])){
      $flight_code = $_POST['flight_code'];
      $date = $_POST['date'];
      $requestedSeats = $_POST['passenger_counts'];
      $data = $flightModel->getFlightsByCode($flight_code, $date, $requestedSeats);
      $columns = ['Авиакомпания', 'Отправление', 'Прибытие', 'Самолет', 'Время вылета', 'Время прилёта', 'Билеты'];
    }


    setlocale(LC_TIME, 'ru_RU.UTF-8'); 
    $dates = [];
    $startDate = new DateTime('today');

    for ($i = 0; $i <= 61; $i++) {
        $isoFormat = $startDate->format('Y-m-d'); 
        $prettyFormat = strftime('%e %B %Y', $startDate->getTimestamp()); 
        $prettyFormat = mb_convert_case(trim($prettyFormat), MB_CASE_TITLE, "UTF-8"); 

        $dates[] = [$isoFormat, $prettyFormat];
        $startDate->modify('+1 day');
    }

    $this->helper->outputCommonHead($title, $menu_active, $styles);
    require_once  './views/charter-number.html';
    $this->helper->outputCommonFoot($scripts);
  }

  public function actionReport($data){
    $incident = $data[0];
    $title = $incident;
    $styles = [CSS . '/report.css'];
    if (isset($_COOKIE['charter_code'])){
      $charter_code = $_COOKIE['charter_code'];
      unset($_COOKIE['charter_code']);
    }
    if (isset($_COOKIE['booking_number'])){
      $booking_number = $_COOKIE['booking_number'];
      unset($_COOKIE['booking_number']);
    }
    $this->helper->outputCommonHead($title, '', $styles);
    require_once  './views/report.html';
    $this->helper->outputCommonFoot();
}


public function actionCharterCheck() {
    if (isset($_POST['charter_code'])){
      $charter_code = $_POST['charter_code'];
      $charterModel = new Charter();
      $data = $charterModel->getCharterRequestByCode($charter_code);
    }
    $title = "Проверка заявки на чартер";
    $styles = [CSS . '/charter.css'];
    $scripts = [];

    $menu_active = 'charter_request';

    $this->helper->outputCommonHead($title, $menu_active, $styles);
    require_once  './views/charter-check.html';
    $this->helper->outputCommonFoot($scripts);
}

  public function actionCharterRequest(){

    if(isset($_POST['submit-btn-charter'])){
      $charterModel = new Charter();
      $departure = $_POST['departure'];
      $arrival = $_POST['arrival'];
      $airline = ($_POST['airline'] == 0) ? NULL : $_POST['airline'];
      if ($airline == "null"){
        $airline = null;
      }
      $flight_date = $_POST['flight-date'];
      $passenger_number = $_POST['passengers-count'];
      if (isset($_POST['allow_other_psng'])){
        $allow_other = true;
      } else{
        $allow_other = false;
      }
      if (isset($_POST['contact-fio'])){
        $user_id = null;
        $fio = $_POST['contact-fio'];
        $email = $_POST['email'];
        $additional_info = $_POST['additional-info'];
        $organization = $_POST['organization'];
      } else{
        $user_id = $_COOKIE['uid'];
      }
      if (is_null($user_id)){
        $res = $charterModel->addWithContact($departure, $arrival, $airline, $flight_date, $passenger_number, $allow_other, $fio, $email, $organization, $additional_info);
      } else{
        $res = $charterModel->addWithUser($departure, $arrival, $airline, $flight_date, $passenger_number, $allow_other, $user_id);
      }
      $request = $charterModel->getCharterRequestById($res);
      setcookie('charter_code', $request['request_code'], time() + 1000, '/');
      header("Location: ./report/999");
      exit;
    }
    $title = "Заявка на чартер";
    $styles = [CSS . '/charter.css'];
    $scripts = ['charter.js', 'notification.js'];

    $airportModel = new Airport();
    $airports = $airportModel->getAll();

    $airlineModel = new Airline();
    $airlines = $airlineModel->getAll();
    $menu_active = 'charter_request';

    $this->helper->outputCommonHead($title, $menu_active, $styles);
    require_once  './views/charter-request.html';
    $this->helper->outputCommonFoot($scripts);
  }

  public function actionCheckIn(){
    $title = "Онлайн регистрация";
    $styles = [CSS . '/check-in.css'];
    $scripts = ['check_in.js', 'notification.js'];

    $bookingModel = new Booking();
    
    $menu_active = 'check_in';


    if (isset($_POST['booking_number'])){

      $data = $bookingModel->getBookingByNumberAndEmail($_POST['reg_email'], $_POST['booking_number']);

      if ($data){
        $dep_time = new DateTime($data[0]['dep_time']);
        $now = new DateTime();
        $interval = $now->diff($dep_time);
        $hours_to_departure = ($dep_time->getTimestamp() - $now->getTimestamp()) / 3600;
        $seats = $bookingModel->getAvailableSeats($data[0]['flight_id']);
      }
    }

    if (isset($_POST['check_in_seat0'])){
      $success = $bookingModel->checkInPassengers($_POST['booking_id'], $_POST);
      if ($success) {
          header("Location: " . FULL_SITE_ROOT . "/report/976");
          exit;
      } else {
          echo "<script>alert('Произошла ошибка при регистрации.');</script>";
      }
    }

    $this->helper->outputCommonHead($title, $menu_active, $styles);
    require_once   './views/common/nav.html';
    require_once  './views/check-in.html';
    $this->helper->outputCommonFoot($scripts);
  }

  public function actionFlightBoard(){
    $title = "Онлайн табло";
    $styles = [CSS . '/flight_board.css'];
    $scripts = [];

    $menu_active = 'flight_board';

    $flightModel = new Flight();

    if (isset($_POST['departure'])){
      $dep = $_POST['departure'];
      $arr = $_POST['arrival'];
      $date = $_POST['date'];
      $flights = $flightModel->getSearchFlights($dep, $arr, $date);
      $data = $flights['data'];
      $columns = $flights['columns'];
    }
    
    $airportModel = new Airport();
    $airports = $airportModel->getAll();

    setlocale(LC_TIME, 'ru_RU.UTF-8'); 
    $dates = [];
    $startDate = new DateTime('yesterday');

    for ($i = 0; $i <= 6; $i++) {
        $isoFormat = $startDate->format('Y-m-d'); 
        $prettyFormat = strftime('%e %B %Y', $startDate->getTimestamp()); 
        $prettyFormat = mb_convert_case(trim($prettyFormat), MB_CASE_TITLE, "UTF-8"); 

        $dates[] = [$isoFormat, $prettyFormat];
        $startDate->modify('+1 day');
    }


    $this->helper->outputCommonHead($title, $menu_active, $styles);
    require_once  './views/flight-board.html';
    $this->helper->outputCommonFoot($scripts);
  }


  public function actionFlightNumber(){
    $title = "Онлайн табло";
    $styles = [CSS . '/flight_board.css'];
    $scripts = [];

    $menu_active = 'flight_board';

    $flightModel = new Flight();

    if (isset($_POST['flight_number'])){
      $flight_number = $_POST['flight_number'];
      $date = $_POST['date'];
      $flights = $flightModel->getSearchFlightsNumber($flight_number, $date);
      $data = $flights['data'];
      $columns = $flights['columns'];
    }


    setlocale(LC_TIME, 'ru_RU.UTF-8'); 
    $dates = [];
    $startDate = new DateTime('yesterday');

    for ($i = 0; $i <= 6; $i++) {
        $isoFormat = $startDate->format('Y-m-d'); 
        $prettyFormat = strftime('%e %B %Y', $startDate->getTimestamp()); 
        $prettyFormat = mb_convert_case(trim($prettyFormat), MB_CASE_TITLE, "UTF-8"); 

        $dates[] = [$isoFormat, $prettyFormat];
        $startDate->modify('+1 day');
    }


    $this->helper->outputCommonHead($title, $menu_active, $styles);
    require_once  './views/flight-number.html';
    $this->helper->outputCommonFoot($scripts);
  }

  public function actionAuth(){
    if ($this->userModel->isAuth()) {
        header("Location: ./profile");
    }

    $num = (int)date("Y");

    $regActive='';
    $authActive='';
    $errors=array();

    if(isset($_POST['signup'])){
      

      $regActive = 'active';
      $authActive = '';

      $name = $_POST['name'];
      $surname = $_POST['surname'];
      $patronymic = $_POST['patronymic'];
      $email = $_POST['email-reg'];
      $gender = $_POST['gender'];
      $birthdate = $_POST['birthdate'];
      $passport = $_POST['passport'];
      $password = $_POST['password-reg'];

      $birthmass = explode("-", $birthdate);

      $day = (int)$birthmass[2];
      $month = (int)$birthmass[1];
      $year = (int)$birthmass[0];

      if (!checkdate($month, $day, $year)){
        $errors['date'] = "Некорректная дата!";
      }

      if ($gender == "М"){
        $gender = 'male';
      } else{
        $gender = 'female';
      }

      $email_check = $this->userModel->checkIfUserExistAuth($email);
      if($email_check != -1){
          $errors['email'] = "Пользователь с этой почтой уже существует (" . $email . ")" ;
      }
      if(count($errors) === 0){
        $password = md5($password);
        $uid = $this->userModel->add($name, $surname, $patronymic, $password, $email, $passport, $birthdate, $gender);

        if ($uid != -1){
          $this->userModel->setAuth($uid);
          header('location: ./profile');
          exit();
        } else{
          $errors['db-error'] = "Ошибка при вставке данных в базу данных!";
        }
      } 
      
    }


    if(isset($_POST['login'])){
      $regActive = '';
      $authActive = 'active';
      $email = $_POST['email'];
      $password = $_POST['password'];
      $hash = md5($password);
      $uid = $this->userModel->checkIfUserExistAuth($email, $hash);
      if($uid != -1){
        if ($uid == 0) {
          $errors['email'] = "Неверный адрес электронной почты или пароль!";
        } else {
          $this->userModel->setAuth($uid);
          header('location: ./profile');
        }
      }else{
          $errors['email'] = "Похоже, ты еще не член aviago! Скорее регистрируйся!";
      }
    }


    require_once  './views/auth.html';

}
}