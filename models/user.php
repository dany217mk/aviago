<?php
class User extends Model
{
    public  function isAuth(){
        if (isset($_COOKIE['uid']) && isset($_COOKIE['t']) && isset($_COOKIE['tt'])){
            $timeToken = $_COOKIE['tt'];
            $query = "SELECT * FROM connect WHERE user_id = :user_id and token = :token";
            $params = ['user_id' => $_COOKIE['uid'], 'token' => $_COOKIE['t']];
            $res = $this->returnActionQuery($query, $params);
            if ($res->rowCount() > 0){
                if (time() > $_COOKIE['tt']){
                    $token = $this->helper->generationToken();
                    $timeToken = time() + 1800;
                    $query = "UPDATE connect SET token = :new_token, time = TO_TIMESTAMP(:timeToken) WHERE user_id = :user_id and token = :token;";
                    $params = ['user_id' => $_COOKIE['uid'], 'token' => $_COOKIE['t'], 'new_token' => $token, 'timeToken' => $timeToken];
                    $this->actionQuery($query, $params);
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
        $query = "DELETE FROM connect WHERE user_id = :user_id";
        $this->actionQuery($query, ['user_id' => $_COOKIE['uid']]);
        setcookie('uid', '', -1, '/');
        setcookie('t', '', -1, '/');
        setcookie('tt', '', -1, '/');
        header('Location: ' . FULL_SITE_ROOT);
    }

    public function getUser(){
      $query = "SELECT user_account.*, role.name as role_name, role.access_level FROM user_account
      LEFT JOIN role ON role.id = user_account.role_id
       WHERE user_account.id = :user_id";
       $params = ['user_id' => $_COOKIE['uid'] ];
      return $this->returnAssoc($query, $params);
    }

    public function getUserInfoById($user_id){
      $query = "SELECT passport_series_number, date_of_birth, gender  FROM user_passenger
       WHERE user_passenger.user_id = :user_id";
       $params = ['user_id' => $user_id ];
      return $this->returnAssoc($query, $params);
    }

    public function checkIfUserExistAuth($email, $password='NONE'){
        $query = "SELECT id, password FROM user_account WHERE email = :email";
        $params = ['email' => $email];
        $res = $this->returnActionQuery($query, $params);
        if ($res->rowCount() == 0){
          return -1;
        }
        $mas = $res->fetch(PDO::FETCH_ASSOC);;
        if ($password != $mas['password']) {
          return 0;
        }
        return $mas['id'];
    }

    public function add($name, $surname, $patronymic, $password, $email, $passport, $birthdate, $gender){
        $this->con->beginTransaction();

        try{
        $query = "INSERT INTO user_account (name, surname, patronymic, password, email)
                      VALUES (:name, :surname, :patronymic, :password, :email)";
            $params = [
                ':name' => $name,
                ':surname' => $surname,
                ':patronymic' => $patronymic,
                ':password' => $password,
                ':email' => $email
            ];
            $this->returnActionQuery($query, $params);
            $userId = $this->con->lastInsertId();
            $queryPassenger = "INSERT INTO user_passenger (user_id, passport_series_number, date_of_birth, gender)
                               VALUES (:user_id, :passport_series_number, :date_of_birth, :gender)";
            $paramsPassenger = [
                ':user_id' => $userId,
                ':passport_series_number' => $passport,
                ':date_of_birth' => $birthdate,
                ':gender' => $gender
            ];
            $this->returnActionQuery($queryPassenger, $paramsPassenger);
            $this->con->commit();
            return $userId;
        } catch (PDOException $e) {
            $this->con->rollBack();
            echo "Ошибка: " . $e->getMessage();
            die;
            return -1;
        }
    }

    public function updateUserPassword($user_id, $password){
        $query = "UPDATE user_account  SET password = :hash
                WHERE id = :user_id;";
        $this->actionQuery($query, ['hash' => $password, 'user_id' => $user_id, ]);
        $query2 = "UPDATE worker_details  SET is_password_changed = true
                WHERE user_id = :user_id;";
        $this->actionQuery($query2, ['user_id' => $user_id, ]);
    }

    public function getPassengerById($user_id){
        $query = "SELECT * FROM user_passenger WHERE user_id = :user_id";
        $data = $this->returnAssoc($query, ['user_id' => $user_id]);
        return $data;
    }


    public function setAuth($uid){
        $token = $this->helper->generationToken();
        $timeToken = time() + 1800;
        $query = "INSERT INTO connect (user_id, token, time) VALUES (:user_id, :token, TO_TIMESTAMP(:timeToken))";
        $params = ['user_id' => $uid, 'token' => $token, 'timeToken' => $timeToken];
        $this->actionQuery($query, $params);
        setcookie('uid', $uid, time() + 2*24*3600, '/');
        setcookie('t', $token, time() + 2*24*3600, '/');
        setcookie('tt', $timeToken, time() + 2*24*3600, '/');
    }

    public function getUserAirline($uid){
        $query = "SELECT airline.*, airport.name as airport_name, airport.iata  as airport_iata FROM airline 
        LEFT JOIN airport ON airport.id = airline.airport_id WHERE airline.user_id = :uid";
        $data = $this->returnAssoc($query, ["uid" => $uid]);
        return $data;

    }

    public function getAirlineByWorker($user_id) {
        $query = "
            SELECT airline.*, airport.name as airport_name, airport.iata as airport_iata
            FROM worker_details
            JOIN airline ON worker_details.airline_id = airline.id
            LEFT JOIN airport ON airport.id = airline.airport_id
            WHERE worker_details.user_id = :user_id AND worker_details.is_active = true
            LIMIT 1
        ";

        return $this->returnAssoc($query, [':user_id' => $user_id]);
    }



}