<?php
class Charter extends Model
{
    public function getAll()
    {
        $query = "SELECT * FROM charter_request";
        $data = $this->returnAllAssoc($query);
        $columns = $this->getColumns('charter_request');

        return ['data' => $data, 'columns' => $columns];
    }

    
}