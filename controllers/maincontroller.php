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
    $scripts = [];
    $styles = [];
    $title  = SITE_NAME;

    $flightModel = new Flight();
    $response = $flightModel->getAll();
    $data = $response['data'];
    $columns = $response['columns'];

    $this->helper->outputCommonHead($title, $styles);
    require_once  './views/home.html';
    $this->helper->outputCommonFoot($scripts);
  }

  public function actionReport($data){
    $incident = $data[0];
    $title = $incident;
    $styles = [CSS . '/report.css'];
    $this->helper->outputCommonHead($title, $styles);
    require_once  './views/report.html';
    $this->helper->outputCommonFoot();
}

  public function actionCharterRequest(){
    $title = "Заявка на чартер";
    $styles = [CSS . '/home.css'];
    $scripts = [];

    $charterModel = new Charter();
    $response = $charterModel->getAll();
    $data = $response['data'];
    $columns = $response['columns'];

    $this->helper->outputCommonHead($title, $styles);
    require_once  './views/charter-request.html';
    $this->helper->outputCommonFoot($scripts);
  }

  public function actionCheckIn(){
    $title = "Онлайн регистрация";
    $styles = [];
    $scripts = [];

    $flightModel = new Flight();
    $response = $flightModel->getAll();
    $data = $response['data'];
    $columns = $response['columns'];

    $this->helper->outputCommonHead($title, $styles);
    require_once  './views/check-in.html';
    $this->helper->outputCommonFoot($scripts);
  }

  public function actionFlightBoard(){
    $title = "Онлайн табло";
    $styles = [];
    $scripts = [];

    $flightModel = new Flight();
    $response = $flightModel->getAll();
    $data = $response['data'];
    $columns = $response['columns'];

    $this->helper->outputCommonHead($title, $styles);
    require_once  './views/flight-board.html';
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