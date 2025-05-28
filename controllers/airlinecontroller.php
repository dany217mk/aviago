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
}