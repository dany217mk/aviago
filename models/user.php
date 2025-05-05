<?php
class User extends Model
{
    public  function isAuth(){
        if (isset($_COOKIE['uid']) && isset($_COOKIE['t']) && isset($_COOKIE['tt'])){
            $timeToken = $_COOKIE['tt'];
            $query = "SELECT * FROM connect WHERE user_id = '" . $_COOKIE['uid'] . "' and token = '" . $_COOKIE['t'] . "'";
            $res = $this->returnActionQuery($query);
            if ($res->rowCount() > 0){
                if (time() > $_COOKIE['tt']){
                    $token = $this->helper->generationToken();
                    $timeToken = time() + 1800;
                    $query = "UPDATE connects SET connect_token = '$token', time = FROM_UNIXTIME('$timeToken') WHERE user_id = '" . $_COOKIE['uid']  . "' and token = '" . $_COOKIE['t']  . "';";
                    $this->actionQuery($query);
                    setcookie('uid', $_COOKIE['uid'], time() + 2*24*3600, '/');
                    setcookie('t', $token, time() + 2*24*3600, '/');
                    setcookie('tt', $timeToken, time() + 2*24*3600, '/');
                }
                return true;
            } else {
              $this->logout();
            }
        }
        return false;
    }

    public function logout(){
        $query = "DELETE FROM `connects` WHERE `connect_user_id` = '" . $_COOKIE['uid'] . "'";
        $this->actionQuery($query);
        setcookie('uid', '', -1, '/');
        setcookie('t', '', -1, '/');
        setcookie('tt', '', -1, '/');
        header('Location: ' . FULL_SITE_ROOT);
    }

    public function getUser(){
      $query = "SELECT *, role.role_name, role.access_level FROM `user_account`
      LEFT JOIN role ON role.id = user.role_id
       WHERE `user_id` = '" . $_COOKIE['uid'] . "'";
      return $this->returnAssoc($query);
    }
}