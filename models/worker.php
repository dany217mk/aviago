<?php
class worker extends Model
{
    public function getAllFromAirline($airline_id){
        $query = "SELECT worker_details.*, user_account.name as worker_name,
         user_account.surname as worker_surname,
         user_account.patronymic as worker_patronymic,
         user_account.email as worker_email, role.name as role_name  FROM worker_details
         LEFT JOIN user_account ON user_account.id = worker_details.user_id
         LEFT JOIN role ON role.id = user_account.role_id WHERE worker_details.airline_id = :airline_id AND worker_details.is_active = true
          ORDER BY user_account.surname, user_account.name, user_account.patronymic";
         $data = $this->returnAllfetchAssoc($query, ["airline_id" => $airline_id]);
         return $data;
    }

    public function add($name, $surname, $patronymic, $hired_at, $role, $position_details, $email, $password, $airline_id){
        $this->con->beginTransaction();

        try{
        $query = "INSERT INTO user_account (name, surname, patronymic, password, email, role_id)
                      VALUES (:name, :surname, :patronymic, :password, :email, :role)";
            $params = [
                ':name' => $name,
                ':surname' => $surname,
                ':patronymic' => $patronymic,
                ':password' => $password,
                ':email' => $email,
                ':role' => $role
            ];
            $this->returnActionQuery($query, $params);
            $userId = $this->con->lastInsertId();
            $queryPassenger = "INSERT INTO worker_details (user_id, hired_at, position_details, airline_id)
                               VALUES (:user_id, :hired_at, :position_details, :airline_id)";
            $paramsWorker = [
                ':user_id' => $userId,
                ':hired_at' => $hired_at,
                ':position_details' => $position_details,
                ':airline_id' => $airline_id
            ];
            $this->returnActionQuery($queryPassenger, $paramsWorker);
            $this->con->commit();
            return $userId;
        } catch (PDOException $e) {
            $this->con->rollBack();
            echo "Ошибка: " . $e->getMessage();
            die;
            return -1;
        }
    }

    public function  getById($id, $user_id){
        $query = "SELECT user_account.id as user_id, user_account.name, user_account.surname, user_account.patronymic, user_account.email,
                        worker_details.hired_at, worker_details.position_details, role.name as role_name, role.id as role_id,
                         worker_details.is_password_changed 
                  FROM worker_details
                  LEFT JOIN user_account ON user_account.id = worker_details.user_id
                  LEFT JOIN role ON role.id = user_account.role_id
                  LEFT JOIN airline ON airline.id = worker_details.airline_id
                   WHERE worker_details.id = :id AND airline.user_id = :user_id";
        $data = $this->returnAssoc($query, [":id" => $id, ":user_id" => $user_id]);
        return $data;
    }

    public function  getByUserId($user_id){
        $query = "SELECT worker_details.*, airline.name as airline_name, airline.logo as airline_logo
                  FROM worker_details
                  LEFT JOIN airline ON airline.id = worker_details.airline_id
                  WHERE worker_details.user_id = :user_id";
        $data = $this->returnAssoc($query, [":user_id" => $user_id]);
        return $data;
    }

    public function edit($name, $surname, $patronymic, $date, $role, $position_details, $email, $user_id, $id){
        $query = "UPDATE user_account  SET name = :name, surname = :surname
         , patronymic = :patronymic, role_id = :role_id, email = :email
          WHERE id = :user_id;";
          $params = [
                ':name' => $name,
                ':surname' => $surname,
                ':patronymic' => $patronymic,
                ':role_id' => $role,
                ':email' => $email,
                ':user_id' => $user_id,
          ];
          $this->actionQuery($query, $params);
          $query2 = "UPDATE worker_details  SET hired_at = :hired_at, position_details = :position_details
          WHERE id = :worker_id;";
          $this->actionQuery($query2, [':hired_at' => $date, ':position_details' => $position_details, ':worker_id' => $id]);
    }

    public function delete($id){
        $query = "UPDATE worker_details  SET is_active = false
          WHERE id = :worker_id;";
          $this->actionQuery($query, ['worker_id' => $id]);
    }
}