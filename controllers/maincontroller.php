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

    $regActive=false;
    $authActive=false;
    $errors=array();
    $title = "Авторизация";
    $styles = [CSS . '/auth.css'];
    $scripts = [];

    require_once  './views/auth.html';

}
}