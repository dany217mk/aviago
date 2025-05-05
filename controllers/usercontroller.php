<?php
class UserController extends Controller
{
    public function __construct(){
        parent::__construct();
    }
    public function actionProfile(){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        echo "Привет " . $this->user['name'] . " " . $this->user['patronymic'];
        echo "<br><a href='./logout'>Выйти</a>";
        exit;
    }

    public function actionLogout(){
        $this->userModel->logout();
      }
}