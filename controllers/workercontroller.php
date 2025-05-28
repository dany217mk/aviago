<?php
class WorkerController extends Controller
{
    
    private $workerModel;
    public function __construct(){
        
        parent::__construct();
        $this->workerModel = new Worker();
    }

    public function actionWorker(){
      if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        $title =  'Работники авиакомпании';
        $scripts = [];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $airline = $this->userModel->getUserAirline($this->user['id']);

        $this->helper->outputCommonHead($title, '', $styles);

        $data = $this->workerModel->getAllFromAirline($airline['id']);

        $type = "worker";

        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/workers.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }


    public function actionDelete($data){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        $id = $data[0];
        $worker = $this->workerModel->getById($id, $this->user['id']);
        if (!$worker){
            header("Location: " . FULL_SITE_ROOT . "/report/125");
            exit;
        }
        $this->workerModel->delete($id);
        header("Location: " . FULL_SITE_ROOT . "/workers");
    }

    public function actionAdd(){
      if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }

        $airline = $this->userModel->getUserAirline($this->user['id']);

        if (isset($_POST['hired_at'])){
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            $patronymic = $_POST['patronymic'];
            $hired_at = $_POST['hired_at'];
            $role = $_POST['role'];
            $position_details = $_POST['position_details'];
            $email = $_POST['email'];
            $password = md5($_POST['password']);

            $hired_at_mas = explode("-", $hired_at);

            $day = (int)$hired_at_mas[2];
            $month = (int)$hired_at_mas[1];
            $year = (int)$hired_at_mas[0];

            $date = "{$year}-{$month}-{$day}"; 

            $email_check = $this->userModel->checkIfUserExistAuth($email);
            if($email_check != -1){
                header("Location: " . FULL_SITE_ROOT . "/report/124");
                exit;
            }


            $this->workerModel->add($name, $surname, $patronymic, $date, $role, $position_details, $email, $password, $airline['id']);
            header("Location: " . FULL_SITE_ROOT . "/workers");
        }



        $title =  'Добавить работника авиакомпании';
        $scripts = ['workers.js'];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $this->helper->outputCommonHead($title, '', $styles);
        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/worker_form.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }

    public function actionEdit($data){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }

        $id = $data[0];

        $worker = $this->workerModel->getById($id, $this->user['id']);
        if (!$worker){
            header("Location: " . FULL_SITE_ROOT . "/report/125");
            exit;
        }

        if (isset($_POST['hired_at'])){
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            $patronymic = $_POST['patronymic'];
            $hired_at = $_POST['hired_at'];
            $role = $_POST['role'];
            $position_details = $_POST['position_details'];
            $email = $_POST['email'];

            $hired_at_mas = explode("-", $hired_at);

            $day = (int)$hired_at_mas[2];
            $month = (int)$hired_at_mas[1];
            $year = (int)$hired_at_mas[0];

            $date = "{$year}-{$month}-{$day}"; 

            $this->workerModel->edit($name, $surname, $patronymic, $date, $role, $position_details, $email, $worker['user_id'], $id);
            
            header("Location: " . FULL_SITE_ROOT . "/workers");
        }
        

        $title =  'Редактирование работника авиакомпании';
        $scripts = ['workers.js'];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $this->helper->outputCommonHead($title, '', $styles);
        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/worker_form.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }



    
}