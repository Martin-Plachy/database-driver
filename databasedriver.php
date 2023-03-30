<?php

class DatabaseDriver {
  
    private static $connection;

    private static $databaseInitOptions = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    );

    public static function connectDatabaseServer($dsn, $username, $password, $dbName)
    {
        self::$connection = new PDO($dsn, $username, $password, self::$databaseInitOptions);

        $sql_query = "CREATE DATABASE IF NOT EXISTS $dbName";

        self::$connection->exec($sql_query);
        
        $sql_query = "USE $dbName";

        self::$connection->exec($sql_query);
    }

    public static function disconnectDatabaseServer()
    {
        self::$connection = null;
    }

    public static function createDatabase($dbName)
    {
        $sql_query = "CREATE DATABASE IF NOT EXISTS $dbName";

        self::$connection->exec($sql_query);
        
        $sql_query = "USE $dbName";

        self::$connection->exec($sql_query);   
    }

    public static function deleteDatabase($dbName)
    {
        $sql_query = "DROP DATABASE IF EXISTS $dbName";

        self::$connection->exec($sql_query); 
    }

    public static function createTable($tableName, $insertedValueArray, $primary_key_index)
    {
        $insertedValueArrayKeys = array_keys($insertedValueArray);
 
        $sql_query = "CREATE TABLE IF NOT EXISTS $tableName (";
  
        for ($i = 0; $i < count($insertedValueArrayKeys); $i++){
            $sql_query .= "$insertedValueArrayKeys[$i] ";

            if (is_int($insertedValueArray[$insertedValueArrayKeys[$i]])) {
                $sql_query .= "INT(10)";
            }else{
                $sql_query .= "VARCHAR(30) NOT NULL";
            }          

            if ($i == $primary_key_index){
                $sql_query .= " PRIMARY KEY";
            }

            if ($i < count($insertedValueArrayKeys)-1){
                $sql_query .= ", ";
            }
        }
        $sql_query .= ")";
        self::$connection->exec($sql_query);   
    }

    public static function deleteTable($tableName)
    {       
        $sql_query = "DROP TABLE IF EXISTS $tableName";

        self::$connection->exec($sql_query); 
    }

    public static function insertOneRowInTable($tableName,$insertedValueArray)
    {
        $insertedValueArrayKeys = array_keys($insertedValueArray);     
        
        $sql_query = "INSERT INTO $tableName(";

        for ($i = 0; $i < count($insertedValueArrayKeys); $i++){
            $sql_query .= "$insertedValueArrayKeys[$i]";
            if ($i < count($insertedValueArrayKeys)-1){
                $sql_query .= ",";
            }
        }

        $sql_query .= ") ";
        $sql_query .= "VALUES(";

        for ($i = 0; $i < count($insertedValueArrayKeys); $i++){
            if(is_int($insertedValueArray[$insertedValueArrayKeys[$i]])){
                $sql_query .= $insertedValueArray[$insertedValueArrayKeys[$i]];
            }else{
                $sql_query .= "'" . $insertedValueArray[$insertedValueArrayKeys[$i]] . "'";
            }
            
            if ($i < count($insertedValueArrayKeys)-1){
                $sql_query .= ",";
            }
        }

        $sql_query .= ")"; 
 
        self::$connection->exec($sql_query);      
    }

    public static function updateOneRowOneValueInTable($tableName, $columnName, $rowValue, $columnToUpdateName, $valueToUpdate)
    {
        $sql_query = "UPDATE $tableName SET $columnToUpdateName = $valueToUpdate WHERE $columnName = $rowValue";

        self::$connection->exec($sql_query); 
    }

    public static function deleteOneRowInTable($tableName, $columnName, $rowValue)
    {
        if(!$columnName){
            $sql_query = "SELECT K.COLUMN_NAME FROM  
            INFORMATION_SCHEMA.TABLE_CONSTRAINTS T
            JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE K
            ON K.CONSTRAINT_NAME=T.CONSTRAINT_NAME  
            WHERE K.TABLE_NAME='$tableName'
            AND T.CONSTRAINT_TYPE='PRIMARY KEY' LIMIT 1";
    
            $statement = self::$connection->query($sql_query);
            $columnNameWithPrimaryKeyArray = $statement->fetch(PDO::FETCH_ASSOC);
            $columnNameWithPrimaryKey = $columnNameWithPrimaryKeyArray['COLUMN_NAME'];
        }else{
            $columnNameWithPrimaryKey = $columnName;
        }

        $sql_query = "DELETE FROM $tableName WHERE $columnNameWithPrimaryKey = $rowValue";

        self::$connection->exec($sql_query);
    }

    public static function readTable($tableName): array
    {
        $sql_query = "SELECT * FROM $tableName";

        $statement = self::$connection->query($sql_query);
        $table = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $table;  
    }
}