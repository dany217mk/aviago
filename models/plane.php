<?php
class Plane extends Model
{
    public function getAllAirplanesFromAirline(int $airline_id) {
        $query = "
            SELECT 
                airplane.*, 
                airplane_airline.registration,
                airplane_airline.id AS airplane_airline_id
            FROM airplane_airline
            JOIN airplane ON airplane.id = airplane_airline.airplane_id
            WHERE airplane_airline.airline_id = :airline_id
            ORDER BY airplane.name
        ";
        $data = $this->returnAllfetchAssoc($query, ['airline_id' => $airline_id]);
        return $data;
    }

    public function getAll() {
        $query = "
            SELECT * 
            FROM airplane
            ORDER BY name
        ";
        $data = $this->returnAllfetchAssoc($query, []);
        return $data;
    }


    public function add($airplane, $registration, $airline_id){
        $this->con->beginTransaction();

        try{
        $query = "INSERT INTO airplane_airline (airplane_id, airline_id, registration)
                      VALUES (:airplane, :airline, :registration)";
            $params = [
                ':airplane' => $airplane,
                ':airline' => $airline_id,
                ':registration' => $registration
            ];
            $this->returnActionQuery($query, $params);
            $this->con->commit();
            return 1;
        } catch (PDOException $e) {
            $this->con->rollBack();
            echo "Ошибка: " . $e->getMessage();
            header("Location: " . FULL_SITE_ROOT . "/report/128");
            die;
            return -1;
        }
    }

    public function getById(int $id, int $user_id) {
        $query = "
            SELECT aa.*
            FROM airplane_airline aa
            JOIN airline ON airline.id = aa.airline_id
            WHERE aa.id = :id AND airline.user_id = :user_id
            LIMIT 1
        ";
        return $this->returnAssoc($query, [':id' => $id, ':user_id' => $user_id]);
    }

    public function  getByUserId($user_id){
        $query = "SELECT worker_details.*, airline.name as airline_name, airline.logo as airline_logo
                  FROM worker_details
                  LEFT JOIN airline ON airline.id = worker_details.airline_id
                  WHERE worker_details.user_id = :user_id";
        $data = $this->returnAssoc($query, [":user_id" => $user_id]);
        return $data;
    }

    public function edit($airplane, $registration, $id) {
    try {
        $this->con->beginTransaction();

        $query = "UPDATE airplane_airline 
                  SET airplane_id = :airplane, registration = :registration
                  WHERE id = :id";
        $params = [
            ':airplane' => $airplane,
            ':registration' => $registration,
            ':id' => $id,
        ];
        $this->actionQuery($query, $params);

        $this->con->commit();
    } catch (PDOException $e) {
        $this->con->rollBack();
        header("Location: " . FULL_SITE_ROOT . "/report/128");
        die;
    }
}


    public function delete(int $id) {
        $query = "DELETE FROM airplane_airline WHERE id = :plane_id;";
        $this->actionQuery($query, ['plane_id' => $id]);
    }

    public function getAirplanesByAirline($airline_id) {
        $query = "
            SELECT aa.id, a.name, a.capacity, aa.registration
            FROM airplane_airline aa
            JOIN airplane a ON aa.airplane_id = a.id
            WHERE aa.airline_id = :airline_id
        ";
        return $this->returnAllfetchAssoc($query, [':airline_id' => $airline_id]);
    }
}