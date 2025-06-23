<?php
class AirlineController extends Controller
{
    
    private $airlineModel;
    public function __construct(){
        
        parent::__construct();
        $this->airlineModel = new Airline();
    }
    public function actionCreate(){
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        $title =  'Создать организацию';
        $scripts = ['create_org.js'];
        $styles = [CSS . '/profile.css', CSS . '/create_org.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);
        
        if (isset($_POST['icao'])){
            if ($_FILES['airline_img']['error'] != 0) {
                $filename = "";
            } else{
                $file = 'airline_img';
                $upload_path = './assets/airline_img';
                if (!is_dir($upload_path)){
                    header("Location: " . FULL_SITE_ROOT . "/report/524");
                    exit;
                }
                $bool = $this->helper->checkImg($file);
                if (!$bool) {
                    header("Location: " . FULL_SITE_ROOT . "/report/524");
                    die;
                }
                $filename = md5(pathinfo($_FILES[$file]['name'], PATHINFO_FILENAME)) . '.' . pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION);
                $allow = false;
                $counterIter = 0;
                while (!$allow) {
                  $counterIter++;
                  $row = $this->airlineModel->get_airline_imgs_filenames($filename);
                  $counter = $row['COUNT(*)'];
                  if ($counter > 0) {
                    $allow = false;
                  } else {
                    $allow = true;
                  }
                  if (!$allow){
                    $filename = md5(pathinfo($_FILES[$file]['name'], PATHINFO_FILENAME)) . '(' . $counterIter . ').' . pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION);
                  }
                }

              move_uploaded_file($_FILES[$file]['tmp_name'], $upload_path . '/' . $filename);
            }
            $name = $_POST['name'];
            $country = $_POST['country'];
            $airport = $_POST['airport'];
            $icao = $_POST['icao'];
            $iata = $_POST['iata'];


            $this->airlineModel->add($name, $country, $airport, $icao, $iata, $this->user['id'], $filename);
            header("Location: " . FULL_SITE_ROOT . "/profile");
        }

        $this->helper->outputCommonHead($title, '', $styles);

        $airportModel = new Airport();

        $airports = $airportModel->getAll();

        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/create_org.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }

    public function actionAirlineCharters(){
      if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        $title =  'Заявки на чартеры';
        $scripts = [];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        if (in_array($this->user['role_id'], array(1))){
          $airline = $this->userModel->getUserAirline($this->user['id']);
        } elseif (in_array($this->user['role_id'], array(2))){
          $airline = $this->userModel->getAirlineByWorker($this->user['id']);
        }


        $charterModel = new Charter();

        $data = $charterModel->getCharterRequestByAirlineId($airline['id']);


        $this->helper->outputCommonHead($title, '', $styles);

        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/airline/charters.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }

    public function actionAddAirlineToCharter($data){
        $charter_id = $data[0];
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        if (in_array($this->user['role_id'], array(1))){
          $airline = $this->userModel->getUserAirline($this->user['id']);
        } elseif (in_array($this->user['role_id'], array(2))){
          $airline = $this->userModel->getAirlineByWorker($this->user['id']);
        }
        $this->airlineModel->addAirlineToCharter($airline['id'], $charter_id);
        header("Location: " . FULL_SITE_ROOT . "/airline_charters");
    }
    
    public function actionAirlineCharter($data){
        $charter_number = $data[0];

        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        $title =  'Заявка на чартер';
        $scripts = ['airline_charter.js', 'notification.js'];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);
        
        if (in_array($this->user['role_id'], array(1))){
          $airline = $this->userModel->getUserAirline($this->user['id']);
        } elseif (in_array($this->user['role_id'], array(2))){
          $airline = $this->userModel->getAirlineByWorker($this->user['id']);
        }
        $charterModel = new Charter();

        $charter = $charterModel->getCharterRequestByAirlineIdAndCharterNumber($airline['id'], $charter_number);

        if (isset($_POST['comment'])){
            $status = trim($_POST['status']);
            $comment = trim($_POST['comment']);

            $charterModel->updateCharterStatusAndComment($charter['id'], $status, $comment);

            header("Location: " . FULL_SITE_ROOT . "/airline_charters");

            exit;
        }

      

        $this->helper->outputCommonHead($title, '', $styles);

        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/airline/airline_charter.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);

    }

    public function actionAirlineFlights(){
      if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        $title =  'Рейсы авиакомпании';
        $scripts = [];
        $styles = [CSS . '/profile.css', CSS . '/workers.css'];
        $menu = $this->helper->getMenu($this->user['access_level']);

        if (in_array($this->user['role_id'], array(1))){
          $airline = $this->userModel->getUserAirline($this->user['id']);
        } elseif (in_array($this->user['role_id'], array(2))){
          $airline = $this->userModel->getAirlineByWorker($this->user['id']);
        }


        $flightModel = new Flight();

        $data = $flightModel->getFlightsByAirlineId($airline['id']);


        $this->helper->outputCommonHead($title, '', $styles);

        echo "<div class='main-block'>";
        require_once  './views/common/menu.html';
        require_once  './views/airline/flights.html';
        echo "</div>";
        $this->helper->outputCommonFoot($scripts);
    }


    
    public function actionFlightArchive($data){
        $flight_code = $data[0];
        if (!$this->userModel->isAuth()) {
            header("Location: ./");
        }
        if (in_array($this->user['role_id'], array(1))){
          $airline = $this->userModel->getUserAirline($this->user['id']);
        } elseif (in_array($this->user['role_id'], array(2))){
          $airline = $this->userModel->getAirlineByWorker($this->user['id']);
        }

        $flightModel = new Flight();

        $flightModel->setFlightArchive($flight_code, $airline['id']);

        header("Location: " . FULL_SITE_ROOT . "/airline_flights");
    }



    public function actionAirlineFlight($data) {
      if (!$this->userModel->isAuth()) {
          header("Location: ./");
          exit;
      }

      $charter_code = $data[0];
      if (in_array($this->user['role_id'], array(1))){
          $airline = $this->userModel->getUserAirline($this->user['id']);
        } elseif (in_array($this->user['role_id'], array(2))){
          $airline = $this->userModel->getAirlineByWorker($this->user['id']);
        }
      $airline_id = $airline['id'];

      $charterModel = new Charter();
      $flightModel = new Flight();
      $airportModel = new Airport();
      $planeModel = new Plane();

      
      $charter = $charterModel->getCharterByCode($charter_code);

      if (!$charter || $charter['airline_id'] != $airline_id) {
          header("Location: " . FULL_SITE_ROOT . "/report/404");
          exit;
      }
     
      $flight = $flightModel->getFlightByCharterId($charter['id']);
      
      $airports = $airportModel->getAllAirports();
      $airplanes = $planeModel->getAirplanesByAirline($airline_id);
      $flight_statuses = $flightModel->getAllFlightStatuses();

      if (isset($_POST['dep_airport_id'])) {
        $dep_airport_id = $_POST['dep_airport_id'];
        $arr_airport_id = $_POST['arr_airport_id'];
        $airplane_airline_id = $_POST['airplane_airline_id'];
        $dep_time = $_POST['dep_time'];
        $arr_time = $_POST['arr_time'];
        $flight_status_id = $_POST['flight_status_id'];
        $allow_public_sales = (isset($_POST['allow_public_sales']) && $_POST['allow_public_sales'] === '1') ? 'true' : 'false';
        $charter_seats_number = (int)$_POST['charter_seats_number'];

        if (!$flight) {
            $flight_code = $flightModel->generateFlightCode();

            $flight_number = $flightModel->generateFlightNumber($airline['iata']);

            try {
                $flightModel->con->beginTransaction();

                $flightModel->add(
                    $dep_airport_id,
                    $arr_airport_id,
                    $flight_status_id,
                    $airplane_airline_id,
                    $dep_time,
                    $arr_time,
                    $flight_number,
                    $allow_public_sales,
                    $flight_code,
                    $charter['id'],
                    $charter_seats_number,
                    $this->user['id']
                );

                $charterModel->updateStatus($charter['id'], 'approved');

                $flightModel->con->commit();

                header("Location: " . FULL_SITE_ROOT . "/airline_flights");
                exit;

            } catch (PDOException $e) {
                $flightModel->con->rollBack();
                if ($e->getCode() === 'P0001' && strpos($e->getMessage(), 'Самолет занят') !== false) {
                    header("Location: " . FULL_SITE_ROOT . "/report/222");
                    exit;
                }
                echo "Ошибка при создании рейса: " . $e->getMessage();
                die;
            }

        } else {
            try {
                $flightModel->edit(
                    $dep_airport_id,
                    $arr_airport_id,
                    $flight_status_id,
                    $airplane_airline_id,
                    $dep_time,
                    $arr_time,
                    $allow_public_sales,
                    $charter_seats_number,
                    $flight['id']
                );

                header("Location: " . FULL_SITE_ROOT . "/airline_flights");
                exit;

            } catch (PDOException $e) {
                echo "Ошибка при редактировании рейса: " . $e->getMessage();
                die;
            }
        }
    }

      
      $title = !empty($flight) ? 'Редактирование рейса' : 'Создание рейса';
      $scripts = ['airline_flight.js', 'notification.js'];
      $styles = [CSS . '/profile.css', CSS . '/workers.css'];

      $menu = $this->helper->getMenu($this->user['access_level']);

      $charter_request_id = $charter['id'];

      $this->helper->outputCommonHead($title, '', $styles);
      echo "<div class='main-block'>";
      require_once './views/common/menu.html';
      require_once './views/airline/airline_flight.html'; 
      echo "</div>";
      $this->helper->outputCommonFoot($scripts);
  }


}