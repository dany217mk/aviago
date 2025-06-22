<?php
class Booking extends Model
{
    public function getBookingInfoByNumberAndEmail(string $email, string $number)
    {
        $query = "
            SELECT booking.id, booking.status, booking.passenger_email,
                   flight.id as flight_id, flight.dep_time,
                   dep.name as dep_airport, arr.name as arr_airport,
                   airline.name as airline_name
            FROM booking
            JOIN flight ON booking.flight_id = flight.id
            JOIN airport dep ON flight.dep_airport_id = dep.id
            JOIN airport arr ON flight.arr_airport_id = arr.id
            JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
            JOIN airline ON airline.id = airplane_airline.airline_id
            WHERE booking.passenger_email = :email AND booking.booking_number = :number
            LIMIT 1
        ";
        return $this->returnAssoc($query, ['email' => $email, 'number' => $number]);
    }

    public function getBookingInfoByNumber(string $number)
    {
        $query = "
            SELECT booking.id, booking.booking_number, booking.status, booking.passenger_email,
                   flight.id as flight_id, flight.dep_time, flight.arr_time, flight.flight_number,
                   dep.name as dep_airport, arr.name as arr_airport,
                   airline.name as airline_name
            FROM booking
            JOIN flight ON booking.flight_id = flight.id
            JOIN airport dep ON flight.dep_airport_id = dep.id
            JOIN airport arr ON flight.arr_airport_id = arr.id
            JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
            JOIN airline ON airline.id = airplane_airline.airline_id
            WHERE booking.booking_number = :number
            LIMIT 1
        ";
        return $this->returnAssoc($query, ['number' => $number]);
    }

    public function getPassengersByBookingId(int $booking_id)
    {
        $query = "
            SELECT 
                pd.id, 
                pd.name, 
                pd.surname, 
                pd.patronymic, 
                pd.passport_series_number,
                bp.id AS booking_passenger_id,
                s.number AS seat_number
            FROM booking_passenger bp
            JOIN passenger_details pd ON bp.passenger_id = pd.id
            LEFT JOIN seat s ON bp.seat_id = s.id
            WHERE bp.booking_id = :booking_id
            ORDER BY pd.surname, pd.name
        ";
        return $this->returnAllfetchAssoc($query, ['booking_id' => $booking_id]);
    }


    public function getBookingData(string $email, string $number)
    {
        $bookingInfo = $this->getBookingInfoByNumberAndEmail($email, $number);

        if (!$bookingInfo) {
            return [
                'booking' => null,
                'passengers' => []
            ];
        }

        $passengers = $this->getPassengersByBookingId((int)$bookingInfo['id']);

        return [
            'booking' => $bookingInfo,
            'passengers' => $passengers
        ];
    }

    public function getBookingDataByNumber($booking_number){
        $bookingInfo = $this->getBookingInfoByNumber($booking_number);

        if (!$bookingInfo) {
            return [
                'booking' => null,
                'passengers' => []
            ];
        }
        $passengers = $this->getPassengersByBookingId((int)$bookingInfo['id']);
        return [
            'booking' => $bookingInfo,
            'passengers' => $passengers
        ];
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