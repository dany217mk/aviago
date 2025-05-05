<?php
class UserController extends Controller
{
    public $userModel;
    public function __construct(){
        $this->userModel = new User();
    }
    public function actionProfile(){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
    }
}