<?php
class Airline extends Model
{
   public function get_airline_imgs_filenames($filename){
      $query = "SELECT COUNT(*) FROM airline WHERE logo = :logo";
        $result = $this->returnAssoc($query, ['logo' => $filename]);
        return (int) $result['count'];
    }

    public function add($name, $country, $airport_id, $icao, $iata, $user_id, $logo){
        try {
            $query = "CALL add_airline_and_update_user(:name, :country, :airport_id, :icao, :iata, :user_id, :logo)";
            $params = [
                ':name' => $name,
                ':country' => $country,
                ':airport_id' => $airport_id,
                ':icao' => $icao,
                ':iata' => $iata,
                ':user_id' => $user_id,
                ':logo' => $logo,
            ];
            $this->actionQuery($query, $params);
        } catch (PDOException $e) {
            header("Location: " . FULL_SITE_ROOT . "/report/525");
            exit;
        }
    }


    public function getAirlineByUserId($user_id){
        $query = "SELECT airline.*, airport.name as airport_name
        FROM airline LEFT JOIN airport ON airport.id = airline.airport_id WHERE user_id = :user_id";
        $data = $this->returnAssoc($query, ['user_id' => $user_id]);
        return $data;
    }

    public function getAll()
    {
        $query = "SELECT * FROM airline";
        $data = $this->returnAllAssoc($query);

        return $data;
    }


    public function addAirlineToCharter($airline_id, $charter_id) {
        try {
            $query = "CALL add_airline_to_charter(:airline_id, :charter_id)";
            $params = [
                ':airline_id' => $airline_id,
                ':charter_id' => $charter_id
            ];
            $this->actionQuery($query, $params);
        } catch (PDOException $e) {
            echo "Ошибка при добавлении авиакомпании к заявке: " . $e->getMessage();
            header("Location: " . FULL_SITE_ROOT . "/report/523");
            die;
        }
    }   
}