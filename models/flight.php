<?php
class Flight extends Model
{
    public function getAll()
    {
        $query = "SELECT * FROM flight";
        $data = $this->returnAllAssoc($query);
        $columns = $this->getColumns('flight');

        return ['data' => $data, 'columns' => $columns];
    }

    
}