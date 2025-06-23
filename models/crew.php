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
        $query = "DELETE FROM crew WHERE id = :crew_id";
        $params = [':crew_id' => $crew_id];
        $this->actionQuery($query, $params);
    }
}