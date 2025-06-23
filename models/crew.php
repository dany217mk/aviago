<?php
class Crew extends Model
{
    public function getAllFlightCrew($flight_code)
    {
        $query = "
            SELECT 
                w.id AS worker_id,
                w.position_details,
                w.hired_at,
                w.is_active,
                ua.name,
                ua.surname,
                ua.patronymic,
                ua.email,
                c.description,
                c.id AS crew_id
            FROM flight f
            INNER JOIN crew c ON f.id = c.flight_id
            INNER JOIN worker_details w ON c.worker_id = w.id
            INNER JOIN user_account ua ON w.user_id = ua.id
            WHERE f.flight_code = :flight_code
        ";

        return $this->returnAllfetchAssoc($query, [':flight_code' => $flight_code]);
    }

    public function getAllAirlineCrew(int $airline_id): array
    {
        $query = "
            SELECT 
                wd.id,
                ua.name,
                ua.surname,
                ua.patronymic,
                ua.email,
                r.name AS role_name,
                wd.position_details,
                wd.hired_at
            FROM worker_details wd
            INNER JOIN user_account ua ON wd.user_id = ua.id
            INNER JOIN role r ON ua.role_id = r.id
            WHERE wd.airline_id = :airline_id
            AND wd.is_active = true
            AND ua.role_id IN (5, 6)
        ";

        return $this->returnAllfetchAssoc($query, [':airline_id' => $airline_id]);
    }

    public function addCrewMember(string $flight_code, int $worker_id, string $description) {
        $query = "
            INSERT INTO crew (flight_id, worker_id, description)
            SELECT f.id, :worker_id, :description
            FROM flight f
            WHERE f.flight_code = :flight_code
            RETURNING id
        ";
        $params = [
            ':worker_id' => $worker_id,
            ':description' => $description,
            ':flight_code' => $flight_code
        ];

        $this->returnActionQuery($query, $params);
    }

    public function deleteById($crew_id) {
        $query = "CALL delete_crew_member(:crew_id)";
        $params = [':crew_id' => $crew_id];
        $this->actionQuery($query, $params);
    }

    public function getMyShedule(int $user_id): array
    {
        $query = "
            SELECT
                f.flight_code,
                f.flight_number,
                f.dep_time,
                f.arr_time,
                a.name AS airline_name,
                ap_dep.name AS dep_airport,
                ap_arr.name AS arr_airport,
                al.name AS airplane_name,
                aa.registration AS airplane_registration,
                al.capacity AS airplane_capacity,
                cr.passenger_count AS charter_passenger_count,
                fs.status_name AS flight_status,
                -- Подсчёт купленных билетов со статусом confirmed или checked-in
                (
                    SELECT COUNT(*)
                    FROM booking b
                    WHERE b.flight_id = f.id
                    AND b.status IN ('confirmed', 'checked-in')
                ) AS tickets_sold
            FROM crew c
            INNER JOIN flight f ON c.flight_id = f.id
            INNER JOIN airplane_airline aa ON f.airplane_airline_id = aa.id
            INNER JOIN airplane al ON aa.airplane_id = al.id
            INNER JOIN airline a ON aa.airline_id = a.id
            INNER JOIN airport ap_dep ON f.dep_airport_id = ap_dep.id
            INNER JOIN airport ap_arr ON f.arr_airport_id = ap_arr.id
            INNER JOIN charter_request cr ON f.charter_request_id = cr.id
            INNER JOIN flight_status fs ON f.flight_status_id = fs.id
            INNER JOIN worker_details wd ON c.worker_id = wd.id
            INNER JOIN user_account ua ON wd.user_id = ua.id
            WHERE ua.id = :user_id
            AND f.dep_time >= NOW()
            ORDER BY f.dep_time ASC
        ";

        return $this->returnAllfetchAssoc($query, [':user_id' => $user_id]);
    }


    public function getMyFlightHistory(int $user_id): array
    {
        $query = "
            SELECT
                f.flight_code,
                f.flight_number,
                f.dep_time,
                f.arr_time,
                a.name AS airline_name,
                ap_dep.name AS dep_airport,
                ap_arr.name AS arr_airport,
                al.name AS airplane_name,
                aa.registration AS airplane_registration,
                al.capacity AS airplane_capacity,
                cr.passenger_count AS charter_passenger_count,
                fs.status_name AS flight_status,
                (
                    SELECT COUNT(*)
                    FROM booking b
                    WHERE b.flight_id = f.id
                    AND b.status IN ('confirmed', 'checked-in')
                ) AS tickets_sold
            FROM crew c
            INNER JOIN flight f ON c.flight_id = f.id
            INNER JOIN airplane_airline aa ON f.airplane_airline_id = aa.id
            INNER JOIN airplane al ON aa.airplane_id = al.id
            INNER JOIN airline a ON aa.airline_id = a.id
            INNER JOIN airport ap_dep ON f.dep_airport_id = ap_dep.id
            INNER JOIN airport ap_arr ON f.arr_airport_id = ap_arr.id
            INNER JOIN charter_request cr ON f.charter_request_id = cr.id
            INNER JOIN flight_status fs ON f.flight_status_id = fs.id
            INNER JOIN worker_details wd ON c.worker_id = wd.id
            INNER JOIN user_account ua ON wd.user_id = ua.id
            WHERE ua.id = :user_id
            AND f.dep_time < NOW()
            ORDER BY f.dep_time DESC
        ";

        return $this->returnAllfetchAssoc($query, [':user_id' => $user_id]);
    }


}