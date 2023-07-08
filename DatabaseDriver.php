<?php

class DatabaseDriver
{
    //  VARIABLE FOR INSTANCE OF PDO CLASS:
    
    private PDO $connection;

    //  INIT OPTIONS:

    private $databaseInitOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    private string $dbName;


    public function __construct()
    {
    }

    //  DATABASE SERVER CONNECTION METHODS:

    public function connectDatabaseServer($args = [], string $configSet, string $dbName)
    {
        $configArray = $args[$configSet];
        
        try
        {
            $this->connection = new PDO
            ($configArray['DB_DSN'] . $configArray['DB_SERVERNAME']. ';port=' . $configArray['DB_PORT'],
            $configArray['DB_USERNAME'],
            $configArray['DB_PASSWORD'],
            $this->databaseInitOptions);
        
            $sqlQuery = "CREATE DATABASE IF NOT EXISTS $dbName";
    
            $this->connection->exec($sqlQuery);
        
            $sqlQuery = "USE $dbName";

            $this->connection->exec($sqlQuery);

        }catch(PDOException $e)
        {
            echo "Připojení k databázi $dbName selhalo: " . $e->getMessage() . "<br>";
        }
    }

    public static function disconnectDatabaseServer()
    {
        $this->connection = null;
    }
}
