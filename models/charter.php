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

    public function getCharterRequestByAirlineId($airline_id) {
        $query = "
            SELECT 
                cr.*, 
                dep.name AS departure_airport, 
                arr.name AS arrival_airport,
                
                COALESCE(ua.email, cc.email) AS user_email,
                COALESCE(ua.surname || ' ' || ua.name || ' ' || ua.patronymic, cc.contact_fio) AS user_fullname,

                cc.organization_name,
                cc.additional_info,

                airline.name AS airline_name,
                creator.email AS airline_creator_email,
                
                f.id AS flight_id,
                f.flight_code,
                f.dep_time,
                f.arr_time

            FROM charter_request cr
            LEFT JOIN airport dep ON cr.departure_airport_id = dep.id
            LEFT JOIN airport arr ON cr.arrival_airport_id = arr.id

            LEFT JOIN user_account ua ON cr.user_id = ua.id
            LEFT JOIN charter_contact cc ON cr.id = cc.request_id

            LEFT JOIN airline ON cr.airline_id = airline.id
            LEFT JOIN user_account creator ON airline.user_id = creator.id

            LEFT JOIN flight f ON f.charter_request_id = cr.id AND f.is_archived = FALSE

            WHERE cr.airline_id = :airline_id
            ORDER BY cr.id DESC
        ";

        return $this->returnAllfetchAssoc($query, [':airline_id' => $airline_id]);
    }


    public function getCharterRequestByAirlineIdAndCharterNumber($airline_id, $charter_number) {
        $query = "
            SELECT 
                cr.*, 
                dep.name AS departure_airport, 
                arr.name AS arrival_airport,
                
                COALESCE(ua.email, cc.email) AS user_email,
                COALESCE(ua.surname || ' ' || ua.name || ' ' || ua.patronymic, cc.contact_fio) AS user_fullname,

                cc.organization_name,
                cc.additional_info,

                airline.name AS airline_name,
                creator.email AS airline_creator_email,
                
                f.id AS flight_id,
                f.flight_code,
                f.dep_time,
                f.arr_time

            FROM charter_request cr
            LEFT JOIN airport dep ON cr.departure_airport_id = dep.id
            LEFT JOIN airport arr ON cr.arrival_airport_id = arr.id

            LEFT JOIN user_account ua ON cr.user_id = ua.id
            LEFT JOIN charter_contact cc ON cr.id = cc.request_id

            LEFT JOIN airline ON cr.airline_id = airline.id
            LEFT JOIN user_account creator ON airline.user_id = creator.id

            LEFT JOIN flight f ON f.charter_request_id = cr.id

            WHERE cr.request_code = :charter_number AND (cr.airline_id = :airline_id OR cr.airline_id IS NULL)
            LIMIT 1
        ";

        return $this->returnAssoc($query, [
            ':airline_id' => $airline_id,
            ':charter_number' => $charter_number
        ]);
    }



    public function updateCharterStatusAndComment($charter_id, $status, $comment) {
        $query = "
            UPDATE charter_request 
            SET status = :status, comment = :comment
            WHERE id = :charter_id
        ";
        $params = [
            ':status' => $status,
            ':comment' => $comment,
            ':charter_id' => $charter_id
        ];
        $this->actionQuery($query, $params);
    }




    public function getCharterByCode($code) {
        $query = "SELECT * FROM charter_request WHERE request_code = :code";
        return $this->returnAssoc($query, [':code' => $code]);
    }


    public function updateStatus($charter_id, $status) {
        $query = "UPDATE charter_request SET status = :status WHERE id = :id";
        $this->actionQuery($query, [':status' => $status, ':id' => $charter_id]);
    }


    
}