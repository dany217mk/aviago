<?php
  class Controller{
    private  $isAuth = false;
    public  $userModel;
    public $user;
    public $helper;


    public function __construct(){
      $this->helper = new Helper();
      $this->userModel = new User();
      $this->isAuth = $this->userModel->isAuth();
      if ($this->isAuth) {
        $this->user = $this->userModel->getUser();
      } else {
        header("Location: " . FULL_SITE_ROOT . "/report/500");
      }
    }


    
}