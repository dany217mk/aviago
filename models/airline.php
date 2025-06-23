<?php
class Airline extends Model
{
   public function get_airline_imgs_filenames($filename){
      $query = "SELECT COUNT(*) FROM airline WHERE logo = :logo";
        $result = $this->returnAssoc($query, ['logo' => $filename]);
        return (int) $result['count'];
    }

    public function add($name, $country, $airport_id, $icao, $iata, $user_id, $logo){
        try{
            $query = "INSERT INTO airline (name, country, airport_id, icao, iata, user_id, logo)
                      VALUES (:name, :country, :airport_id, :icao, :iata, :user_id, :logo)";
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
        }  catch (PDOException $e) {
            header("Location: " . FULL_SITE_ROOT . "/report/525");
            exit;
        }
        

             $query2 = "UPDATE user_account SET role_id = 1 WHERE id = :user_id";
             $this->actionQuery($query2, [':user_id' => $user_id]);
        
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
            $query = "UPDATE charter_request SET airline_id = :airline_id WHERE id = :charter_id";
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