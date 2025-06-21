<?php
class Booking extends Model
{
    public function getBookingByNumberAndEmail($email, $number){
        $query = "SELECT booking.id, dep.name as dep_airport, arr.name as arr_airport, airline.name as airline_name,
        flight.dep_time, pd.name, pd.surname, pd.patronymic, flight.id as flight_id, bp.id as booking_passenger_id, booking.status
         FROM booking
         JOIN flight ON booking.flight_id = flight.id
         JOIN airport dep ON flight.dep_airport_id = dep.id
         JOIN airport arr ON flight.arr_airport_id = arr.id
         JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
         JOIN airline ON airline.id = airplane_airline.airline_id
         JOIN booking_passenger bp ON booking.id = bp.booking_id
         JOIN passenger_details pd ON bp.passenger_id = pd.id
         WHERE booking.passenger_email = :email AND booking.booking_number = :number ORDER BY pd.surname, pd.name";
         $data = $this->returnAllfetchAssoc($query, [
                'email' => $email,
                'number' => $number
            ]);
        return $data;
    }


    public function getAvailableSeats($flight_id) {
        $query = "
            SELECT s.id, s.number, s.type, s.is_emergency_exit
            FROM flight f
            JOIN airplane_airline aa ON f.airplane_airline_id = aa.id
            JOIN airplane a ON aa.airplane_id = a.id
            JOIN seat s ON s.airplane_id = a.id
            WHERE f.id = :flight_id
            AND s.id NOT IN (
                SELECT bp.seat_id
                FROM booking_passenger bp
                JOIN booking b ON bp.booking_id = b.id
                WHERE b.flight_id = :flight_id
                    AND bp.seat_id IS NOT NULL
            )
            ORDER BY  s.number;
        ";

        return $this->returnAllfetchAssoc($query, ['flight_id' => $flight_id]);
    }



    public function checkInPassengers($booking_id, $post_data) {
    $this->con->beginTransaction();

    try {
        $stmtBooking = $this->con->prepare("UPDATE booking SET status = 'checked-in' WHERE id = :booking_id");
        $stmtBooking->execute([':booking_id' => $booking_id]);

        foreach ($post_data as $key => $value) {
            if (strpos($key, 'check_in_seat') === 0 && strpos($value, '-') !== false) {
                list($booking_passenger_id, $seat_id) = explode('-', $value);

                $stmtBP = $this->con->prepare("UPDATE booking_passenger SET seat_id = :seat_id WHERE id = :bp_id");
                $stmtBP->execute([
                    ':seat_id' => $seat_id,
                    ':bp_id' => $booking_passenger_id
                ]);
            }
        }

        $this->con->commit();
        return true;
    } catch (PDOException $e) {
        $this->con->rollBack();
        echo "Ошибка при онлайн-регистрации: " . $e->getMessage();
        return false;
    }
}



}