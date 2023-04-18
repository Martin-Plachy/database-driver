<?php

class DatabaseDriver {
  
    //  VARIABLE FOR INSTANCE OF PDO CLASS:

    private static $connection;

    //  INIT OPTIONS:

    private static $databaseInitOptions = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    );

    //  PRIVATE CLASS METHODS:

    private static function getColumnWithPrimaryKey(string $tableName): string
    {
        $sqlQuery = "SELECT K.COLUMN_NAME FROM  
        INFORMATION_SCHEMA.TABLE_CONSTRAINTS T
        JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE K
        ON K.CONSTRAINT_NAME=T.CONSTRAINT_NAME  
        WHERE K.TABLE_NAME='$tableName'
        AND T.CONSTRAINT_TYPE='PRIMARY KEY' LIMIT 1";
        
        try
        {
            $statement = self::$connection->query($sqlQuery);
            $columnNameWithPrimaryKeyArray = $statement->fetch(PDO::FETCH_ASSOC);
            return $columnNameWithPrimaryKeyArray['COLUMN_NAME'];        
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
            return "";
        }
        
    }

    private static function isMultiDimArrayEmpty(array $rowValues): bool
    {
        if(count($rowValues)){
            $rowValueAmount = 0;
            foreach ($rowValues as $rowValue) {
                $rowValueAmount += count($rowValue);
            }
            if($rowValueAmount){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }  
    }

    private static function iterateMultiDimArray(string $sqlQuery, array $columnName, array $rowValues): string
    {
        for ($i = 0; $i < count($columnName); $i++)
        {
            $j = 0;
            foreach ($rowValues[$i] as $value)
            if ($j < count($rowValues[$i])-1)
            {
                $sqlQuery .= "$columnName[$i] = :value_" . $i . "_" . $j . " OR ";
                $j++;
            }else{
                if($i < count($columnName)-1)
                {
                    $sqlQuery .= "$columnName[$i] = :value_" . $i . "_" . $j . " OR ";
                }else{
                    $sqlQuery .= "$columnName[$i] = :value_" . $i . "_" . $j;
                }               
                $j = 0;
            } 
        }
        return $sqlQuery;       
    }

    //  DATABASE SERVER CONNECTION METHODS:

    public static function connectDatabaseServer(string $dsn, string $username, string $password, string $dbName)
    {
        try
        {
            self::$connection = new PDO($dsn, $username, $password, self::$databaseInitOptions);

            $sqlQuery = "CREATE DATABASE IF NOT EXISTS $dbName";
    
            self::$connection->exec($sqlQuery);
            
            $sqlQuery = "USE $dbName";
    
            self::$connection->exec($sqlQuery);
        }catch(PDOException $e)
        {
            echo "Připojení k databázi $dbName selhalo: " . $e->getMessage() . "<br>";
        }
    }

    public static function disconnectDatabaseServer()
    {
        self::$connection = null;
    }

    //  SQL STATEMENTS FOR A DATABASE:

    public static function createDatabase(string $dbName)
    {
        $sqlQuery = "CREATE DATABASE IF NOT EXISTS $dbName";

        self::$connection->exec($sqlQuery);
        
        $sqlQuery = "USE $dbName";

        self::$connection->exec($sqlQuery);   
    }

    public static function deleteDatabase(string $dbName)
    {
        $sqlQuery = "DROP DATABASE IF EXISTS $dbName";

        try
        {
            self::$connection->exec($sqlQuery); 
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
        }
    }

    //  CRUD STATEMENTS FOR A TABLE:

    public static function createTable(string $tableName, array|string $rowValues, int $primary_key_index, bool $createFromInputString = false)
    {       
        if(is_array($rowValues))
            {
                foreach($rowValues as $rowValue)
                {
                    if(is_array($rowValue))
                    {
                        foreach($rowValue as $value)
                        {
                            $inputValuesArrayKeys = array_keys($rowValue);
                            $inputValues = $rowValue;
                            break;
                        }
                    }else{
                        $inputValuesArrayKeys = array_keys($rowValues);
                        $inputValues = $rowValues;
                    }        
                }
            }
             
            $sqlQuery = "CREATE TABLE IF NOT EXISTS $tableName (";
      
            if(!$createFromInputString)
            {
                for ($i = 0; $i < count($inputValuesArrayKeys); $i++){
                    $sqlQuery .= "$inputValuesArrayKeys[$i] ";
        
                    if(DateTime::createFromFormat('Y-m-d', $inputValues[$inputValuesArrayKeys[$i]]))
                    {
                        $sqlQuery .= "DATE NOT NULL";
                    }else{
                        if (is_numeric($inputValues[$inputValuesArrayKeys[$i]])) {
                            if(is_int($inputValues[$inputValuesArrayKeys[$i]])) {
                                $sqlQuery .= "INT(10) NOT NULL";    
                            }else{
                                $sqlQuery .= "VARCHAR(30) NOT NULL";
                            }
                        }else{
                            if($inputValues[$inputValuesArrayKeys[$i]] == null){
                                $sqlQuery .= "VARCHAR(30) NULL";
                            }else{
                                $sqlQuery .= "VARCHAR(30) NOT NULL";
                            }
                        } 
                    }        
        
                    if ($i == $primary_key_index){
                        $sqlQuery .= " PRIMARY KEY";
                    }
        
                    if ($i < count($inputValuesArrayKeys)-1){
                        $sqlQuery .= ", ";
                    }
                }
            }else{
                $sqlQuery .= "$inputValues";
            }
            $sqlQuery .= ")";

            try
            {
                self::$connection->exec($sqlQuery);
            }catch(Error $e)
            {
                echo "Nastala chyba: " . $e->getMessage() . "<br>";   
            }
    }

    public static function readTable(string $tableName, array $columnName = [], array $rowValues = [[]]): array
    {             
        if(count($columnName) && self::isMultiDimArrayEmpty($rowValues) && count($columnName) == count($rowValues))
        {   
            $sqlQuery = self::iterateMultiDimArray("SELECT * FROM $tableName WHERE ", $columnName, $rowValues);

        }else{
            $sqlQuery = "SELECT * FROM $tableName";
        } 
        
        try
        {
            $statement = self::$connection->query($sqlQuery);
            $table = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $table; 
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
            return [];
        }
    }

    public static function insertRowsInTable(string $tableName, array $rowValues)
    {       
        $inputValuesArrayKeys = [];
        
        foreach ($rowValues as $rowValue)
            {
                $inputValuesArrayKeys = array_keys($rowValue);
            }
            
            $sqlQuery = "INSERT INTO $tableName(";
    
            for ($i = 0; $i < count($inputValuesArrayKeys); $i++){
                $sqlQuery .= "$inputValuesArrayKeys[$i]";
                if ($i < count($inputValuesArrayKeys)-1){
                    $sqlQuery .= ",";
                }
            }
    
            $sqlQuery .= ") ";
            $sqlQuery .= "VALUES(";
    
            for ($i = 0; $i < count($inputValuesArrayKeys); $i++){
                $sqlQuery .= ":$inputValuesArrayKeys[$i]";
                if ($i < count($inputValuesArrayKeys)-1){
                    $sqlQuery .= ",";
                }
            }
    
            $sqlQuery .= ") ";

            try
            {
                $sqlQueryPrepared = self::$connection->prepare($sqlQuery);
    
                foreach($rowValues as $rowValue)
                { 
                    for ($i = 0; $i < count($inputValuesArrayKeys); $i++){
                            
                            $sqlQueryPrepared->bindParam(':' . $inputValuesArrayKeys[$i],$rowValue[$inputValuesArrayKeys[$i]]);
                    }
    
                    try
                    {
                        $sqlQueryPrepared->execute();
                    }catch(PDOException $e)
                    {
                        echo date("d. m. Y H:i:s - ") . $e->getMessage() . "<br>";
                    }
                        
                }  
            }catch(Error $e)
            {
                echo "Nastala chyba: " . $e->getMessage() . "<br>";
            }
    }

    public static function deleteTable(string $tableName, array $columnName, array $rowValues)
    {
        if(count($columnName) && self::isMultiDimArrayEmpty($rowValues) && count($columnName) == count($rowValues))
        {
            $sqlQuery = self::iterateMultiDimArray("DELETE FROM $tableName WHERE ", $columnName, $rowValues);
        }else{
            $sqlQuery = "DROP TABLE IF EXISTS $tableName";
        } 

        try
        {     
            $sqlQueryPrepared = self::$connection->prepare($sqlQuery);
            foreach ($rowValues as $keyI => $rowValue)
            {
                foreach ($rowValue as $keyJ => $value)
                {    
                    $sqlQueryPrepared->bindValue(':value_' . $keyI . '_' . $keyJ,$value);
                }
            }
            $sqlQueryPrepared->execute();   
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
        }
    }

    //  CRUD STATEMENTS FOR ONE ROW:

    public static function insertOneRowInTable(string $tableName, array $rowValue)
    {
        $rowValueArrayKeys = array_keys($rowValue);     
        
        $sqlQuery = "INSERT INTO $tableName(";

        for ($i = 0; $i < count($rowValueArrayKeys); $i++){
            $sqlQuery .= "$rowValueArrayKeys[$i]";
            if ($i < count($rowValueArrayKeys)-1){
                $sqlQuery .= ",";
            }
        }

        $sqlQuery .= ") ";
        $sqlQuery .= "VALUES(";

        for ($i = 0; $i < count($rowValueArrayKeys); $i++){
            $sqlQuery .= ":$rowValueArrayKeys[$i]";
            if ($i < count($rowValueArrayKeys)-1){
                $sqlQuery .= ",";
            }
        }

        $sqlQuery .= ")";

        try
        {
            $sqlQueryPrepared = self::$connection->prepare($sqlQuery);

            for ($i = 0; $i < count($rowValueArrayKeys); $i++)
            {           
                $sqlQueryPrepared->bindParam(':' . $rowValueArrayKeys[$i],$rowValue[$rowValueArrayKeys[$i]]);
            }
            $sqlQueryPrepared->execute();
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
        }
    }

    public static function updateOneRowOneValueInTable(string $tableName, int|string $rowValue, string $columnName, int|string $valueToUpdate)
    {
        $columnNameWithPrimaryKey = self::getColumnWithPrimaryKey($tableName);
        
        $sqlQuery = "UPDATE $tableName SET $columnName = :valueToUpdate WHERE $columnNameWithPrimaryKey = :rowValue";
        
        try
        {
            $sqlQueryPrepared = self::$connection->prepare($sqlQuery);
            $sqlQueryPrepared->bindParam(':valueToUpdate',$valueToUpdate);
            $sqlQueryPrepared->bindParam(':rowValue',$rowValue);
            $sqlQueryPrepared->execute();      
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
        }
    }

    public static function readOneRowInTable(string $tableName, int|string $rowValue): array
    {
        $columnNameWithPrimaryKey = self::getColumnWithPrimaryKey($tableName);

        $sqlQuery = "SELECT * FROM $tableName WHERE $columnNameWithPrimaryKey = :rowValue";

        try
        {
            $sqlQueryPrepared = self::$connection->prepare($sqlQuery);
            $sqlQueryPrepared->bindParam(':rowValue',$rowValue);
            $sqlQueryPrepared->execute(); 
            $row = $sqlQueryPrepared->fetch(PDO::FETCH_ASSOC);
            return $row;
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
            return [];
        }   
    }

    public static function deleteOneRowInTable(string $tableName, int|string $rowValue)
    {
        $columnNameWithPrimaryKey = self::getColumnWithPrimaryKey($tableName);

        $sqlQuery = "DELETE FROM $tableName WHERE $columnNameWithPrimaryKey = :rowValue";

        try
        {
            $sqlQueryPrepared = self::$connection->prepare($sqlQuery);
            $sqlQueryPrepared->bindParam(':rowValue',$rowValue);
            $sqlQueryPrepared->execute(); 
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
        }
    }
}