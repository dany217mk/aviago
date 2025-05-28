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
        $title =  $this->user['name'] . " " . $this->user['patronymic'];
        $styles = [CSS . '/profile.css'];

        $access_level = $this->user['access_level']; 

        if ($access_level == 5){
         $footer_active = true;
        }

        $menu = $this->helper->getMenu($access_level); 

        if (in_array($this->user['role_id'], array(2, 5, 6))){
            $workerModel = new Worker();
            $user_worker = $workerModel->getByUserId($this->user['id']);
            if (!$user_worker['is_active']){
                $this->userModel->logout();
                header('Location: ./report/123');
                exit;
            }
            if (!$user_worker['is_password_changed']){
                header('Location: ./change_password');
            }
            $this->helper->outputCommonHead($title, '', $styles);
            echo "<div class='main-block'>";
            require_once  './views/common/menu.html';
            require_once  './views/profile/profile_worker.html';
            echo "</div>";
            require_once   './views/common/footer.html';
            require_once   './views/common/foot.html';
        } 

        if (in_array($this->user['role_id'], array(3))){
            $user_passenger = $this->userModel->getPassengerById($this->user['id']);
            $this->helper->outputCommonHead($title, '', $styles);
            echo "<div class='main-block'>";
            require_once  './views/common/menu.html';
            require_once  './views/profile/profile_passenger.html';
            echo "</div>";
            require_once   './views/common/footer.html';
            require_once   './views/common/foot.html';
        } 

        if (in_array($this->user['role_id'], array(1))){
            $airlineModel = new Airline();
            $user_airline = $airlineModel->getAirlineByUserId($this->user['id']);
            $this->helper->outputCommonHead($title, '', $styles);
            echo "<div class='main-block'>";
            require_once  './views/common/menu.html';
            require_once  './views/profile/profile_admin_airline.html';
            echo "</div>";
            require_once   './views/common/footer.html';
            require_once   './views/common/foot.html';
        } 
        
        

        
    }


    public function actionChangePassword(){
        $errors=array();
        if (isset($_POST['new_password'])){
            $new_password = $_POST['new_password'];
            $hash = md5($new_password);
            $this->userModel->updateUserPassword($this->user['id'], $hash);
            header('location: ./profile');
        }
        require_once  './views/change_password.html';
    }

    public function actionLogout(){
        $this->userModel->logout();
      }
}