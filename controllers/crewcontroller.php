<?php
class CrewController extends Controller
{
    private $crewModel; 
    public function __construct(){
        parent::__construct();
        $this->crewModel = new Crew();
    }

    public function actionCrewFlight($data){
        $flight_code = $data[0];

        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        if (in_array($this->user['role_id'], array(1))){
          $airline = $this->userModel->getUserAirline($this->user['id']);
        } elseif (in_array($this->user['role_id'], array(2, 5, 6))){
          $airline = $this->userModel->getAirlineByWorker($this->user['id']);
        }

         $title =  'Экипаж рейса';
         $scripts = ['crew.js', 'notification.js'];
         $styles = [CSS . '/profile.css', CSS . '/workers.css'];
         $menu = $this->helper->getMenu($this->user['access_level']);


        $data = $this->crewModel->getAllFlightCrew($flight_code);

        if (isset($_POST['worker_id'])){
            $worker_id = (int)$_POST['worker_id'];
            $description = trim($_POST['flight_role'] ?? '');
            try {
                $this->crewModel->addCrewMember($flight_code, $worker_id, $description);
                header("Location: " . FULL_SITE_ROOT . "/crew_flight/" . $flight_code);
                exit;
            } catch (PDOException $e) {
                $errorMessage = $e->getMessage();

                if (strpos($errorMessage, 'Этот сотрудник уже назначен на этот рейс') !== false) {
                    header("Location: " . FULL_SITE_ROOT . "/report/444");
                    exit;
                } elseif (strpos($errorMessage, 'Член экипажа занят на другом рейсе') !== false) {
                    header("Location: " . FULL_SITE_ROOT . "/report/555");
                    exit;
                } else {
                    echo "Ошибка при добавлении члена экипажа: " . $errorMessage;
                    die;
                }
            }
        }

        $workers = $this->crewModel->getAllAirlineCrew($airline['id']);


        $this->helper->outputCommonHead($title, '', $styles);

        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/airline/crew_flight.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }

    public function actionCrewDelete($data){
        $crew_id = $data[0];
        $this->crewModel->deleteById($crew_id);
        if (!empty($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: " . FULL_SITE_ROOT . "/airline_flights");
        }
        exit;
    }

    public function actionMySchedule(){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        $title =  'Мое расписание';
        $scripts = [];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $airline = $this->userModel->getUserAirline($this->user['id']);

        $this->helper->outputCommonHead($title, '', $styles);

        $data = $this->crewModel->getMyShedule($this->user['id']);


        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/crew/my_schedule.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }

    public function actionMyFlightHistory(){
         if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        $title =  'Мое расписание';
        $scripts = [];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $airline = $this->userModel->getUserAirline($this->user['id']);

        $this->helper->outputCommonHead($title, '', $styles);

        $data = $this->crewModel->getMyFlightHistory($this->user['id']);


        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/crew/my_flight_history.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }

}