<?php
class Airport extends Model
{
    public function getAll()
    {
        $query = "SELECT * FROM airport";
        $data = $this->returnAllAssoc($query);

        return $data;
    }

    
}