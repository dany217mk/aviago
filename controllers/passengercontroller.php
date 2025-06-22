<?php
class PassengerController extends Controller
{
    private $passengerModel;
    public function __construct(){
        parent::__construct();
        $this->passengerModel = new Passenger();
    }
    public function actionMyTickets(){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        if (!in_array($this->user['role_id'], array(3))){
            header("Location: ./report/125");
        }
        $title =  'Мои билеты';
        $scripts = [];
        $styles = [CSS . '/profile.css', CSS . '/passenger.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $this->helper->outputCommonHead($title, '', $styles);

        $passenger = $this->passengerModel->getByUserId($this->user['id']);

        $data = $this->passengerModel->getCurrentPassengerTickets($this->user['surname'], $passenger['passport_series_number']);

        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/passenger/my_tickets.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }
    public function actionHistory(){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        if (!in_array($this->user['role_id'], array(3))){
            header("Location: ./report/125");
        }
        $title =  'История покупок';
        $scripts = [];
        $styles = [CSS . '/profile.css', CSS . '/passenger.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $this->helper->outputCommonHead($title, '', $styles);

        $passenger = $this->passengerModel->getByUserId($this->user['id']);

        $data = $this->passengerModel->getAllPassengerTickets($this->user['surname'], $passenger['passport_series_number']);

        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/passenger/history.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }
    public function actionMyRequests(){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        if (!in_array($this->user['role_id'], array(3))){
            header("Location: ./report/125");
        }
        $title =  'Мои заявки на чартерные рейсы';
        $scripts = [];
        $styles = [CSS . '/profile.css', CSS . '/passenger.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        $this->helper->outputCommonHead($title, '', $styles);

        $passenger = $this->passengerModel->getByUserId($this->user['id']);

        $data = $this->passengerModel->getPassengerCharterRequests($this->user['id']);

        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/passenger/my_requests.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }

    public function actionFlightCharterInfo($data){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        if (!in_array($this->user['role_id'], array(3))){
            header("Location: ./report/125");
        }
        $flight_code = $data[0];

        $styles = [CSS . '/profile.css', CSS . '/ticket-info.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);
        $scripts = [];

        $title = "Информация о рейсе";
    
        $flightModel = new Flight();

        $result = $flightModel->getFlightCharterDataByCode($flight_code);

        if ($result['flight']){
            $flightInfo = $result['flight'];
            $passengers = $result['passengers'];
            if ($flightInfo['charter_user_id'] != $this->user['id']){
                header("Location: " . FULL_SITE_ROOT . "/report/125");
            }
        } else{
            header("Location: " . FULL_SITE_ROOT . "/report/404");
        }


        $this->helper->outputCommonHead($title, '', $styles);
        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/passenger/flight-info-charter.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }

}