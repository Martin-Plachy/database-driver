<?php

class DatabaseDriver {
  
    private static $connection;

    private static $databaseInitOptions = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    );

    private static function getColumnWithPrimaryKey(string $tableName): string
    {
        $sql_query = "SELECT K.COLUMN_NAME FROM  
        INFORMATION_SCHEMA.TABLE_CONSTRAINTS T
        JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE K
        ON K.CONSTRAINT_NAME=T.CONSTRAINT_NAME  
        WHERE K.TABLE_NAME='$tableName'
        AND T.CONSTRAINT_TYPE='PRIMARY KEY' LIMIT 1";

        $statement = self::$connection->query($sql_query);
        $columnNameWithPrimaryKeyArray = $statement->fetch(PDO::FETCH_ASSOC);
        return $columnNameWithPrimaryKeyArray['COLUMN_NAME'];
    }

    public static function connectDatabaseServer(string $dsn, string $username, string $password, string $dbName)
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

    public static function createDatabase(string $dbName)
    {
        $sql_query = "CREATE DATABASE IF NOT EXISTS $dbName";

        self::$connection->exec($sql_query);
        
        $sql_query = "USE $dbName";

        self::$connection->exec($sql_query);   
    }

    public static function deleteDatabase(string $dbName)
    {
        $sql_query = "DROP DATABASE IF EXISTS $dbName";

        self::$connection->exec($sql_query); 
    }

    public static function createTable(string $tableName, array|string $inputValues, int $primary_key_index, bool $createFromInputArray)
    {
        if(is_array($inputValues))
        {
            $inputValuesArrayKeys = array_keys($inputValues);
        }
         
        $sql_query = "CREATE TABLE IF NOT EXISTS $tableName (";
  
        if($createFromInputArray)
        {
            for ($i = 0; $i < count($inputValuesArrayKeys); $i++){
                $sql_query .= "$inputValuesArrayKeys[$i] ";
    
                if(DateTime::createFromFormat('Y-m-d', $inputValues[$inputValuesArrayKeys[$i]]))
                {
                    $sql_query .= "DATE NOT NULL";
                }else{
                    if (is_int($inputValues[$inputValuesArrayKeys[$i]])) {
                    
                        $sql_query .= "INT(10) NOT NULL";
                    }else{
                        if($inputValues[$inputValuesArrayKeys[$i]] == null){
                            $sql_query .= "VARCHAR(30) NULL";
                        }else{
                            $sql_query .= "VARCHAR(30) NOT NULL";
                        }
                    } 
                }        
    
                if ($i == $primary_key_index){
                    $sql_query .= " PRIMARY KEY";
                }
    
                if ($i < count($inputValuesArrayKeys)-1){
                    $sql_query .= ", ";
                }
            }
        }else{
            $sql_query .= "$inputValues";
        }
        $sql_query .= ")";
        self::$connection->exec($sql_query);
    }

    public static function deleteTable(string $tableName)
    {       
        $sql_query = "DROP TABLE IF EXISTS $tableName";

        self::$connection->exec($sql_query); 
    }

    public static function insertOneRowInTable(string $tableName, array $inputValues)
    {
        $inputValuesArrayKeys = array_keys($inputValues);     
        
        $sql_query = "INSERT INTO $tableName(";

        for ($i = 0; $i < count($inputValuesArrayKeys); $i++){
            $sql_query .= "$inputValuesArrayKeys[$i]";
            if ($i < count($inputValuesArrayKeys)-1){
                $sql_query .= ",";
            }
        }

        $sql_query .= ") ";
        $sql_query .= "VALUES(";

        for ($i = 0; $i < count($inputValuesArrayKeys); $i++){
            if(is_int($inputValues[$inputValuesArrayKeys[$i]])){
                $sql_query .= $inputValues[$inputValuesArrayKeys[$i]];
            }else{
                $sql_query .= "'" . $inputValues[$inputValuesArrayKeys[$i]] . "'";
            }
            
            if ($i < count($inputValuesArrayKeys)-1){
                $sql_query .= ",";
            }
        }

        $sql_query .= ")"; 
 
        self::$connection->exec($sql_query);      
    }

    public static function updateOneRowOneValueInTable(string $tableName, string $columnName, int|string $rowValue, string $columnToUpdateName, int|string $valueToUpdate)
    {
        $sql_query = "UPDATE $tableName SET $columnToUpdateName = $valueToUpdate WHERE $columnName = $rowValue";

        self::$connection->exec($sql_query); 
    }

    public static function deleteOneRowInTable(string $tableName, string $columnName, int|string $rowValue)
    {
        if(!$columnName){
            $columnNameWithPrimaryKey = self::getColumnWithPrimaryKey($tableName);
        }else{
            $columnNameWithPrimaryKey = $columnName;
        }

        $sql_query = "DELETE FROM $tableName WHERE $columnNameWithPrimaryKey = $rowValue";

        self::$connection->exec($sql_query);
    }

    public static function readTable(string $tableName): array
    {
        $sql_query = "SELECT * FROM $tableName";

        $statement = self::$connection->query($sql_query);
        $table = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $table;  
    }

    public static function readOneRowInTable(string $tableName, string $columnName, int|string $rowValue): array
    {
        if(!$columnName){
            $columnNameWithPrimaryKey = self::getColumnWithPrimaryKey($tableName);
        }else{
            $columnNameWithPrimaryKey = $columnName;
        }

        $sql_query = "SELECT * FROM $tableName WHERE $columnNameWithPrimaryKey = $rowValue";

        $statement = self::$connection->query($sql_query);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row;
    }

}