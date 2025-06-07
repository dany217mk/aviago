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
         flight.dep_time, flight.arr_time, flight.distance FROM flight
         LEFT JOIN airport dep ON flight.dep_airport_id = dep.id
         LEFT JOIN airport arr ON flight.arr_airport_id = arr.id
         LEFT JOIN flight_status ON flight.flight_status_id = flight_status.id
         LEFT JOIN airplane_airline ON flight.airplane_airline_id = airplane_airline.id
         LEFT JOIN airplane ON airplane.id = airplane_airline.airplane_id
         LEFT JOIN airline ON airline.id = airplane_airline.airline_id
         WHERE flight.dep_airport_id = :dep AND flight.arr_airport_id = :arr AND flight.dep_time::date = :date
         ORDER BY flight.dep_time";
         $data = $this->returnAllfetchAssoc($query, ['dep' => $dep, 'arr' => $arr, 'date' => $date]);
         $columns = ['Отправление', 'Прибытие', 'Авиакомпания', 'Самолет', 'Статус', 'Время вылета', 'Время прилёта', 'Дистанция в км'];

        return ['data' => $data, 'columns' => $columns];
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

    
}