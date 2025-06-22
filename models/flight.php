<?php
class Flight extends Model
{
    public function getAll()
    {
        $query = "SELECT dep.name as dep_airport, arr.name as arr_airport, airline.name as airline_name, airplane.name as airplane_name, 
         flight_status.status_name as status_name,
         flight.dep_time, flight.arr_time, flight.distance FROM flight
         LEFT JOIN airport dep ON flight.dep_airport_id = dep.id
         LEFT JOIN airport arr ON flight.arr_airport_id = arr.id
         LEFT JOIN flight_status ON flight.flight_status_id = flight_status.id
         LEFT JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
         LEFT JOIN airplane ON airplane.id = airplane_airline.airplane_id
         LEFT JOIN airline ON airline.id = airplane_airline.airline_id
         ORDER BY flight.id DESC";
        $data = $this->returnAllAssoc($query);
        $columns = ['Отправление', 'Прибытие', 'Авиакомпания', 'Самолет', 'Статус', 'Время вылета', 'Время прилёта', 'Дистанция в км'];

        return ['data' => $data, 'columns' => $columns];
    }

    public function getSearchFlights($dep, $arr, $date){
        $query = "SELECT dep.name as dep_airport, arr.name as arr_airport, airline.name as airline_name, airplane.name as airplane_name, 
         flight_status.status_name as status_name,
         flight.dep_time, flight.arr_time FROM flight
         LEFT JOIN airport dep ON flight.dep_airport_id = dep.id
         LEFT JOIN airport arr ON flight.arr_airport_id = arr.id
         LEFT JOIN flight_status ON flight.flight_status_id = flight_status.id
         LEFT JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
         LEFT JOIN airplane ON airplane.id = airplane_airline.airplane_id
         LEFT JOIN airline ON airline.id = airplane_airline.airline_id
         WHERE flight.dep_airport_id = :dep AND flight.arr_airport_id = :arr AND flight.dep_time::date = :date
         ORDER BY flight.dep_time";
         $data = $this->returnAllfetchAssoc($query, ['dep' => $dep, 'arr' => $arr, 'date' => $date]);
         $columns = ['Отправление', 'Прибытие', 'Авиакомпания', 'Самолет', 'Статус', 'Время вылета', 'Время прилёта'];

        return ['data' => $data, 'columns' => $columns];
    }

    public function getFlightByNumber($number){
        $query = "SELECT flight.id, dep.name as dep_airport, arr.name as arr_airport, airline.name as airline_name, airplane.name as airplane_name, 
                    flight.dep_time, flight.arr_time, flight.flight_number, flight.flight_code FROM flight
                    LEFT JOIN airport dep ON flight.dep_airport_id = dep.id
                    LEFT JOIN airport arr ON flight.arr_airport_id = arr.id
                    LEFT JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
                    LEFT JOIN airplane ON airplane.id = airplane_airline.airplane_id
                    LEFT JOIN airline ON airline.id = airplane_airline.airline_id
                    WHERE flight.flight_number = :number";
        $data = $this->returnAssoc($query, ['number' => $number]);
        return $data;
    }

    public function getFlightByCode($code){
        $query = "SELECT flight.id, dep.name as dep_airport, arr.name as arr_airport, airline.name as airline_name, airplane.name as airplane_name, 
                    flight.dep_time, flight.arr_time, flight.flight_number, flight.flight_code FROM flight
                    LEFT JOIN airport dep ON flight.dep_airport_id = dep.id
                    LEFT JOIN airport arr ON flight.arr_airport_id = arr.id
                    LEFT JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
                    LEFT JOIN airplane ON airplane.id = airplane_airline.airplane_id
                    LEFT JOIN airline ON airline.id = airplane_airline.airline_id
                    WHERE flight.flight_code = :code";
        $data = $this->returnAssoc($query, ['code' => $code]);
        return $data;
    }

    public function getFlightsWithSeats($dep, $arr, $date, $requestedSeats){
        $query = "
        SELECT 
        flight.id AS flight_id,
        dep.name AS dep_airport,
        arr.name AS arr_airport,
        airline.name AS airline_name,
        airline.logo AS airline_logo,
        airplane.name AS airplane_name,
        flight_status.status_name AS status_name,
        flight.dep_time AS dep_time,
        flight.arr_time AS arr_time,
        airplane.capacity,
        flight.flight_code,
        flight.flight_number,
        flight.charter_seats_number,
        
        COUNT(bp.id) FILTER (
            WHERE b.status IN ('reserved', 'confirmed', 'checked-in') 
            AND b.charter_request_id IS NULL
        ) AS total_individual_booked,
        
        (airplane.capacity - flight.charter_seats_number - 
        COUNT(bp.id) FILTER (
            WHERE b.status IN ('reserved', 'confirmed', 'checked-in') 
            AND b.charter_request_id IS NULL
        )) AS available_for_individuals

        FROM flight
        LEFT JOIN airport dep ON flight.dep_airport_id = dep.id
        LEFT JOIN airport arr ON flight.arr_airport_id = arr.id
        LEFT JOIN flight_status ON flight.flight_status_id = flight_status.id
        LEFT JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
        LEFT JOIN airplane ON airplane.id = airplane_airline.airplane_id
        LEFT JOIN airline ON airline.id = airplane_airline.airline_id
        LEFT JOIN booking b ON b.flight_id = flight.id
        LEFT JOIN booking_passenger bp ON bp.booking_id = b.id

        WHERE flight.dep_airport_id = :dep
        AND flight.arr_airport_id = :arr
        AND flight.dep_time::date = :date
        AND flight.allow_public_sales = true
        AND flight.dep_time > NOW() + INTERVAL '1 hour'

        GROUP BY 
            flight.id, dep.name, arr.name, airline.name, airline.logo, airplane.name, 
            flight_status.status_name, flight.dep_time, flight.arr_time, 
            airplane.capacity, flight.charter_seats_number

        ORDER BY flight.dep_time";


        $data = $this->returnAllfetchAssoc($query, [
                'dep' => $dep,
                'arr' => $arr,
                'date' => $date
            ]);
        return $data;
    }


    public function getFlightsByCode($flight_code, $date, $passenger_counts){
        $query = "
            SELECT 
                flight.id AS flight_id,
                dep.name AS dep_airport,
                arr.name AS arr_airport,
                airline.name AS airline_name,
                airline.logo AS airline_logo,
                airplane.name AS airplane_name,
                flight.dep_time AS dep_time,
                flight.arr_time AS arr_time,
                flight.flight_code,
                flight.flight_number,
                flight.charter_seats_number,
                flight.charter_request_id,

                COUNT(bp.id) FILTER (
                    WHERE b.status IN ('reserved', 'confirmed', 'checked-in')
                    AND b.charter_request_id = flight.charter_request_id
                ) AS booked_by_charter,

                (flight.charter_seats_number - 
                COUNT(bp.id) FILTER (
                    WHERE b.status IN ('reserved', 'confirmed', 'checked-in')
                    AND b.charter_request_id = flight.charter_request_id
                )) AS charter_available

            FROM flight
            LEFT JOIN airport dep ON flight.dep_airport_id = dep.id
            LEFT JOIN airport arr ON flight.arr_airport_id = arr.id
            LEFT JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
            LEFT JOIN airplane ON airplane.id = airplane_airline.airplane_id
            LEFT JOIN airline ON airline.id = airplane_airline.airline_id
            LEFT JOIN booking b ON b.flight_id = flight.id
            LEFT JOIN booking_passenger bp ON bp.booking_id = b.id

            WHERE flight.flight_code = :flight_code
            AND flight.dep_time::date = :date
            AND flight.dep_time > NOW() + INTERVAL '1 hour'

            GROUP BY 
                flight.id,
                flight.flight_code,
                flight.flight_number,
                flight.charter_seats_number,
                flight.charter_request_id,
                dep.name,
                arr.name,
                airline.name,
                airline.logo,
                airplane.name,
                flight.flight_code,
                flight.dep_time,
                flight.arr_time
        ";

        $data = $this->returnAllfetchAssoc($query, [
                'flight_code' => $flight_code,
                'date' => $date
            ]);
        return $data;

    }




    public function bookFlightPassengers($flight_id, $post_data, $charterRequestId=null) {
    $this->con->beginTransaction();

    try {
        $booking_number = $this->con->query("SELECT generate_booking_number()")->fetchColumn();

        $query = "INSERT INTO booking (flight_id, status, booking_number, passenger_email, charter_request_id)
                  VALUES (:flight_id, 'reserved',  :number, :email, :charter_id)";
        $stmt = $this->con->prepare($query);
        $stmt->execute([
            ':flight_id' => $flight_id,
            ':number' => $booking_number,
            ':charter_id' => $charterRequestId,
            ":email" => $post_data['book_email'],
        ]);

        $booking_id = $this->con->lastInsertId();

        $i = 1;
        while (isset($post_data["book_name{$i}"])) {
            $name = trim($post_data["book_name{$i}"]);
            $surname = trim($post_data["book_surname{$i}"]);
            $patronymic = trim($post_data["book_patronymic{$i}"] ?? '');
            $passport = preg_replace('/\s+/', '', $post_data["book_passport{$i}"]);

            $stmtPassenger = $this->con->prepare("INSERT INTO passenger_details (name, surname, patronymic, passport_series_number)
                                                  VALUES (:name, :surname, :patronymic, :passport)
                                                  RETURNING id");
            $stmtPassenger->execute([
                ':name' => $name,
                ':surname' => $surname,
                ':patronymic' => $patronymic,
                ':passport' => $passport
            ]);
            $passenger_id = $stmtPassenger->fetchColumn();

            $stmtBP = $this->con->prepare("INSERT INTO booking_passenger (booking_id, passenger_id)
                                           VALUES (:booking_id, :passenger_id)");
            $stmtBP->execute([
                ':booking_id' => $booking_id,
                ':passenger_id' => $passenger_id
            ]);

            $i++;
        }

        $this->con->commit();
        return $booking_number;
    } catch (PDOException $e) {
        $this->con->rollBack();
        echo "Ошибка: " . $e->getMessage();
        die;
    }
}


    public function getSearchFlightsNumber($flight_number, $date){
        $query = "SELECT dep.name as dep_airport, arr.name as arr_airport, airline.name as airline_name, airplane.name as airplane_name, 
         flight_status.status_name as status_name,
         flight.dep_time, flight.arr_time, flight.distance FROM flight
         LEFT JOIN airport dep ON flight.dep_airport_id = dep.id
         LEFT JOIN airport arr ON flight.arr_airport_id = arr.id
         LEFT JOIN flight_status ON flight.flight_status_id = flight_status.id
         LEFT JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
         LEFT JOIN airplane ON airplane.id = airplane_airline.airplane_id
         LEFT JOIN airline ON airline.id = airplane_airline.airline_id
         WHERE flight.flight_number = :flight_number AND flight.dep_time::date = :date
         ORDER BY flight.dep_time";
         $data = $this->returnAllfetchAssoc($query, ['flight_number' => $flight_number, 'date' => $date]);
         $columns = ['Отправление', 'Прибытие', 'Авиакомпания', 'Самолет', 'Статус', 'Время вылета', 'Время прилёта', 'Дистанция в км'];

        return ['data' => $data, 'columns' => $columns];
    }



    public function getFlightCharterDataByCode(string $flight_code) {
        $flightInfo = $this->getFlightInfoByCode($flight_code);
        if (!$flightInfo) {
            return [
                'flight' => null,
                'passengers' => []
            ];
        }
        $passengers = $this->getPassengersByFlightCharterId((int)$flightInfo['charter_request_id']);
        return [
            'flight' => $flightInfo,
            'passengers' => $passengers
        ];
    }

    public function getFlightInfoByCode(string $flight_code) {
        $query = "
            SELECT 
                f.id, f.flight_code, f.flight_number, f.dep_time, f.arr_time,
                dep.name as dep_airport, arr.name as arr_airport,
                airline.name as airline_name,
                cr.user_id as charter_user_id,
                cr.id as charter_request_id,
                airplane.name as airplane_name   
            FROM flight f
            JOIN airport dep ON f.dep_airport_id = dep.id
            JOIN airport arr ON f.arr_airport_id = arr.id
            JOIN airplane_airline aa ON f.airplane_airline_id = aa.id
            JOIN airplane ON airplane.id = aa.airplane_id  
            JOIN airline ON airline.id = aa.airline_id
            JOIN charter_request cr ON f.charter_request_id = cr.id
            WHERE f.flight_code = :flight_code
            LIMIT 1
        ";
        return $this->returnAssoc($query, ['flight_code' => $flight_code]);
    }

    public function getPassengersByFlightCharterId(int $charter_request_id) {
        $query = "
            SELECT
                pd.id,
                pd.name,
                pd.surname,
                pd.patronymic,
                pd.passport_series_number,
                bp.id AS booking_passenger_id,
                s.number AS seat_number,
                b.booking_number,
                b.status,
                b.passenger_email
            FROM booking_passenger bp
            JOIN passenger_details pd ON bp.passenger_id = pd.id
            JOIN booking b ON bp.booking_id = b.id
            LEFT JOIN seat s ON bp.seat_id = s.id
            WHERE b.charter_request_id = :charter_request_id
            ORDER BY pd.surname, pd.name
        ";
        return $this->returnAllfetchAssoc($query, ['charter_request_id' => $charter_request_id]);
    }

    
}