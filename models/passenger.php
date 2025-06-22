<?php
class Passenger extends Model
{
    public function getByUserId($user_id){
        $query = "SELECT passport_series_number, date_of_birth, gender  FROM user_passenger
            WHERE user_passenger.user_id = :user_id";
            $params = ['user_id' => $user_id ];
        return $this->returnAssoc($query, $params);
    }

    public function getCurrentPassengerTickets($surname, $passport) {
        $query = "
            SELECT 
                b.id AS booking_id,
                b.booking_number,
                b.status AS booking_status,
                b.passenger_email,
                f.id AS flight_id,
                f.dep_time,
                f.arr_time,
                dep.name AS dep_airport,
                arr.name AS arr_airport,
                al.name AS airline_name,
                al.logo AS airline_logo,
                a.name AS airplane_name,
                COUNT(bp_all.id) AS passenger_count
            FROM booking b
            JOIN flight f ON b.flight_id = f.id
            JOIN airport dep ON f.dep_airport_id = dep.id
            JOIN airport arr ON f.arr_airport_id = arr.id
            JOIN airplane_airline aa ON f.airplane_airline_id = aa.id
            JOIN airline al ON al.id = aa.airline_id
            JOIN airplane a ON a.id = aa.airplane_id
            JOIN booking_passenger bp_user ON bp_user.booking_id = b.id
            JOIN passenger_details pd ON pd.id = bp_user.passenger_id
            JOIN booking_passenger bp_all ON bp_all.booking_id = b.id
            WHERE 
                LOWER(pd.surname) = LOWER(:surname)
                AND REPLACE(pd.passport_series_number, ' ', '') = REPLACE(:passport, ' ', '')
                AND f.arr_time > NOW()
            GROUP BY 
                b.id, f.id, dep.name, arr.name, al.name, al.logo, a.name
            ORDER BY f.dep_time ASC
        ";

        return $this->returnAllfetchAssoc($query, [
            'surname' => $surname,
            'passport' => $passport
        ]);
    }

    public function getAllPassengerTickets($surname, $passport) {
        $query = "
            SELECT 
                b.id AS booking_id,
                b.booking_number,
                b.status AS booking_status,
                b.passenger_email,
                f.id AS flight_id,
                f.dep_time,
                f.arr_time,
                dep.name AS dep_airport,
                arr.name AS arr_airport,
                al.name AS airline_name,
                al.logo AS airline_logo,
                a.name AS airplane_name,
                COUNT(bp_all.id) AS passenger_count
            FROM booking b
            JOIN flight f ON b.flight_id = f.id
            JOIN airport dep ON f.dep_airport_id = dep.id
            JOIN airport arr ON f.arr_airport_id = arr.id
            JOIN airplane_airline aa ON f.airplane_airline_id = aa.id
            JOIN airline al ON al.id = aa.airline_id
            JOIN airplane a ON a.id = aa.airplane_id
            JOIN booking_passenger bp_user ON bp_user.booking_id = b.id
            JOIN passenger_details pd ON pd.id = bp_user.passenger_id
            JOIN booking_passenger bp_all ON bp_all.booking_id = b.id
            WHERE 
                LOWER(pd.surname) = LOWER(:surname)
                AND REPLACE(pd.passport_series_number, ' ', '') = REPLACE(:passport, ' ', '')
            GROUP BY 
                b.id, f.id, dep.name, arr.name, al.name, al.logo, a.name
            ORDER BY f.dep_time ASC
        ";

        return $this->returnAllfetchAssoc($query, [
            'surname' => $surname,
            'passport' => $passport
        ]);
    }

    public function getPassengerCharterRequests($user_id) {
        $query = "
            SELECT 
                cr.id AS request_id,
                cr.request_code,
                cr.passenger_count,
                cr.status,
                cr.allow_public_sales,
                cr.comment,
                cr.departure_date,
                dep.name AS departure_airport,
                arr.name AS arrival_airport,
                airline.name AS airline_name,
                creator.email AS airline_creator_email,
                f.id AS flight_id,
                f.flight_number,
                f.flight_code,
                f.dep_time,
                f.arr_time
            FROM charter_request cr
            LEFT JOIN airport dep ON cr.departure_airport_id = dep.id
            LEFT JOIN airport arr ON cr.arrival_airport_id = arr.id
            LEFT JOIN airline ON cr.airline_id = airline.id
            LEFT JOIN user_account creator ON airline.user_id = creator.id
            LEFT JOIN flight f ON f.charter_request_id = cr.id
            WHERE cr.user_id = :user_id
            ORDER BY cr.departure_date ASC
        ";
        
        return $this->returnAllfetchAssoc($query, ['user_id' => $user_id]);
    }
}