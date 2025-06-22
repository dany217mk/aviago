<?php
class PlaneController extends Controller
{
    
    private $planeModel;
    public function __construct(){
        
        parent::__construct();
        $this->planeModel = new Plane();
    }

    public function actionPlane(){
      if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        $title =  'Самолеты авиакомпании';
        $scripts = [];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $airline = $this->userModel->getUserAirline($this->user['id']);

        $this->helper->outputCommonHead($title, '', $styles);

        $data = $this->planeModel->getAllAirplanesFromAirline($airline['id']);

        $type = "plane";

        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/admin/planes.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }


    public function actionDelete($data){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        $id = $data[0];
        $plane = $this->planeModel->getById($id, $this->user['id']);
        if (!$plane){
            header("Location: " . FULL_SITE_ROOT . "/report/125");
            exit;
        }
        $this->planeModel->delete($id);
        header("Location: " . FULL_SITE_ROOT . "/planes");
    }

    public function actionAdd(){
      if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }

        $airline = $this->userModel->getUserAirline($this->user['id']);

        $planes = $this->planeModel->getAll();

        if (isset($_POST['airplane'])){
            $airplane = $_POST['airplane'];
            $registration = $_POST['registration'];
            $this->planeModel->add($airplane, $registration, $airline['id']);
            header("Location: " . FULL_SITE_ROOT . "/planes");
        }

        $title =  'Добавить самолет авиакомпании';
        $scripts = ['planes.js', 'notification.js'];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $this->helper->outputCommonHead($title, '', $styles);
        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/admin/plane_form.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }

    public function actionEdit($data){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }

        $id = $data[0];

        $plane = $this->planeModel->getById($id, $this->user['id']);
        if (!$plane){
            header("Location: " . FULL_SITE_ROOT . "/report/125");
            exit;
        }

        $planes = $this->planeModel->getAll();

        if (isset($_POST['airplane'])){
            $airplane = $_POST['airplane'];
            $registration = $_POST['registration'];

            $this->planeModel->edit($airplane, $registration, $id);
            
            header("Location: " . FULL_SITE_ROOT . "/planes");
        }
        

        $title =  'Редактирование самолета авиакомпании';
        $scripts = ['planes.js', 'notification.js'];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $this->helper->outputCommonHead($title, '', $styles);
        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/admin/plane_form.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }



    
}