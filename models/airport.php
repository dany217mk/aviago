<?php
class Airport extends Model
{
    public function getAll()
    {
        $query = "SELECT * FROM airport";
        $data = $this->returnAllAssoc($query);

        return $data;
    }

    public function getAllAirports() {
        return $this->returnAllAssoc("SELECT * FROM airport ORDER BY city, name");
    }

    
}