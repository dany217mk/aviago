<?php
class Charter extends Model
{
    public function getAll()
    {
        $query = "SELECT * FROM charter_request";
        $data = $this->returnAllAssoc($query);
        $columns = $this->getColumns('charter_request');

        return ['data' => $data, 'columns' => $columns];
    }

    public function addWithContact($departure, $arrival, $airline, $flight_date, $passenger_number, $allow_other, $fio, $email, $organization, $additional_info){
        $this->con->beginTransaction();

        try{
        $query = "INSERT INTO charter_request
         (request_code, departure_airport_id, arrival_airport_id, passenger_count, departure_date, airline_id, allow_public_sales, status)
                      VALUES ( (SELECT generate_request_code()), :departure, :arrival, :num, :date, :airline, :allow, :status)";
            $params = [
                ':departure' => $departure,
                ':arrival' => $arrival,
                ':num' => $passenger_number,
                ':date' => $flight_date,
                ':airline' => $airline,
                ':allow' => $allow_other ? 'true' : 'false',
                ':status' => 'pending'
            ];
            $this->returnActionQuery($query, $params);
            $charterId = $this->con->lastInsertId();
            $queryPassenger = "INSERT INTO charter_contact (request_id, contact_fio, email, organization_name, additional_info)
                               VALUES (:request_id, :contact_fio, :email, :organization_name, :additional_info)";
            $paramsWorker = [
                ':request_id' => $charterId,
                ':contact_fio' => $fio,
                ':email' => $email,
                ':organization_name' => $organization,
                ':additional_info' => $additional_info,
            ];
            $this->returnActionQuery($queryPassenger, $paramsWorker);
            $this->con->commit();
            return $charterId;
        } catch (PDOException $e) {
            $this->con->rollBack();
            echo "Ошибка: " . $e->getMessage();
            die;
            return -1;
        }
    }

    public function addWithUser($departure, $arrival, $airline, $flight_date, $passenger_number, $allow_other, $user_id){
        $this->con->beginTransaction();

        try{
        $query = "INSERT INTO charter_request
         (request_code, departure_airport_id, arrival_airport_id, passenger_count, departure_date, airline_id, allow_public_sales, status, user_id)
                      VALUES ((SELECT generate_request_code()), :departure, :arrival, :num, :date, :airline, :allow, :status, :user_id)";
            $params = [
                ':departure' => $departure,
                ':arrival' => $arrival,
                ':num' => $passenger_number,
                ':date' => $flight_date,
                ':airline' => $airline,
                ':allow' => $allow_other ? 'true' : 'false',
                ':status' => 'pending',
                ':user_id' => $user_id
            ];
            $this->returnActionQuery($query, $params);
            $charterId = $this->con->lastInsertId();
            $this->con->commit();
            return $charterId;
        } catch (PDOException $e) {
            $this->con->rollBack();
            echo "Ошибка: " . $e->getMessage();
            die;
            return -1;
        }
    }

    public function getCharterRequestById($request_id){
        $query = "SELECT * FROM charter_request WHERE id = :request_id";
        $data = $this->returnAssoc($query, [":request_id" => $request_id]);
        return $data;
    }

    public function getCharterRequestByCode($code){
        $query = "SELECT * FROM charter_request WHERE request_code = :code";
        $data = $this->returnAssoc($query, [":code" => $code]);
        return $data;
    }

    
}