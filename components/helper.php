<?php 
class Helper{
    public function outputCommonHead($title, $menu_active='', $styles=[]){
        require_once   './views/common/head.html';
        require_once   './views/common/header.html';
        require_once   './views/common/nav.html';
      }
    
      public function outputCommonFoot($scripts=[]){
        require_once   './views/common/foot.html';
      }

      public function getMenu($access_level){
        $menu = array();

        if ($access_level == 10){
             $menu[] = array("name" => "Личный кабинет", "link" => "profile", "icon" => '<i class="fa-solid fa-user"></i>');
             $menu[] = array("name" => "Работники а/к", "link" => "workers", "icon" => '<i class="fa-solid fa-user-group"></i>');
             $menu[] = array("name" => "Рейсы а/к", "link" => "/efef", "icon" => '<i class="fa-solid fa-plane-departure"></i>');
             $menu[] = array("name" => "Заявки на чартеры", "link" => "efef", "icon" => '<i class="fa-solid fa-comments"></i>');
             $menu[] = array("name" => "Самолеты", "link" => "planes", "icon" => '<i class="fa-solid fa-plane"></i>');
             $menu[] = array("name" => "Инструкции", "link" => "report/503", "icon" => '<i class="fa-solid fa-circle-question"></i>');
        } elseif ($access_level == 8){
            $menu[] = array("name" => "Личный кабинет", "link" => "/profile", "icon" => '<i class="fa-solid fa-user"></i>');
            $menu[] = array("name" => "Рейсы а/к", "link" => "efef", "icon" => '<i class="fa-solid fa-plane-departure"></i>');
            $menu[] = array("name" => "Заявки на чартеры", "link" => "efef", "icon" => '<i class="fa-solid fa-comments"></i>');        
        } elseif ($access_level == 6){
            $menu[] = array("name" => "Личный кабинет", "link" => "/profile", "icon" => '<i class="fa-solid fa-user"></i>');
            $menu[] = array("name" => "Мое расписание", "link" => "efef", "icon" => '<i class="fa-solid fa-calendar-days"></i>');
            $menu[] = array("name" => "История рейсов", "link" => "efef", "icon" => '<i class="fa-solid fa-clock-rotate-left"></i>');    
        } else{
           $menu[] = array("name" => "Личный кабинет", "link" => "/profile", "icon" => '<i class="fa-solid fa-user"></i>');
            $menu[] = array("name" => "Мои билеты", "link" => "/my_tickets", "icon" => '<i class="fa-solid fa-ticket"></i>');
            $menu[] = array("name" => "История покупок", "link" => "/history", "icon" => '<i class="fa-solid fa-clock-rotate-left"></i>');
            $menu[] = array("name" => "Заявки на чартеры", "link" => "/my_requests", "icon" => '<i class="fa-solid fa-comments"></i>'); 
        }

        return $menu;
      }

      public function generationToken($size = 32){
        $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $token = "";
        for ($i=0; $i<$size; $i++) {
          $rnd = rand(0, strlen($chars)-1);
          $token .= substr($chars, $rnd, 1);
        }
        return $token;
    }

    public function generationRequestCode(){
      
    }

    public function checkImg($val)
        {
          $bool = true;
          $allowed = array('gif', 'png', 'jpeg', 'jpg');
          $filename = $_FILES[$val]['name'];
          $ext = pathinfo($filename, PATHINFO_EXTENSION);
          if (!in_array($ext, $allowed)) {
            header("Location: " . FULL_SITE_ROOT . "/report/522");
            die;
            $bool = false;
          } else {
            if ($_FILES[$val]['size'] > 10000000){
              header("Location: " . FULL_SITE_ROOT . "/report/522");
              die;
              $bool = false;
            }
          }
          return $bool;
        }


}