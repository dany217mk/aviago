<?php
class Model
{
    public PDO $con;

    public $helper;

    public function __construct()
    {
        $this->con = DB::getConnection(); 
        $this->helper = new Helper();
    }

    public function getColumns(string $table): array
    {
        $query = "SELECT column_name FROM information_schema.columns WHERE table_name = :table";
        $stmt = $this->con->prepare($query);
        $stmt->bindParam(':table', $table);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function returnAllAssoc(string $query): array
    {
        $stmt = $this->con->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function returnActionQuery($query, $params = []) {
        $stmt = $this->con->prepare($query); 
        $stmt->execute($params);             
        return $stmt;                        
    }

    public function actionQuery($query, $params) {
        $stmt = $this->con->prepare($query); 
        $stmt->execute($params);  
    }

    public function returnAssoc($query, $params = []) {
        $stmt = $this->con->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function returnAllfetchAssoc($query, $params = [])
    {
        $stmt = $this->con->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}