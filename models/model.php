<?php
class Model
{
    private PDO $con;

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

    public function returnActionQuery($query) {
            return $this->con->query($query);
    }

    public function actionQuery($query) {
        $this->con->exec($query); 
    }

    public function returnAssoc($query) {
        $stmt = $this->con->query($query);   
        return $stmt->fetch(PDO::FETCH_ASSOC);    
    }
}