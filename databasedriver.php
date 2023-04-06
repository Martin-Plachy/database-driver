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
        $sql_query = "SELECT K.COLUMN_NAME FROM  
        INFORMATION_SCHEMA.TABLE_CONSTRAINTS T
        JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE K
        ON K.CONSTRAINT_NAME=T.CONSTRAINT_NAME  
        WHERE K.TABLE_NAME='$tableName'
        AND T.CONSTRAINT_TYPE='PRIMARY KEY' LIMIT 1";
        
        try
        {
            $statement = self::$connection->query($sql_query);
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

    private static function iterateMultiDimArray(string $sql_query, array $columnName, array $rowValues): string
    {
        for ($i = 0; $i < count($columnName); $i++)
        {
            $j = 0;
            foreach ($rowValues[$i] as $value)
            if ($j < count($rowValues[$i])-1)
            {
                $sql_query .= "$columnName[$i] = $value OR ";
                $j++;
            }else{
                if($i < count($columnName)-1)
                {
                    $sql_query .= "$columnName[$i] = $value OR ";
                }else{
                    $sql_query .= "$columnName[$i] = $value";
                }               
                $j = 0;
            } 
        }
        return $sql_query;       
    }

    //  DATABASE SERVER CONNECTION METHODS:

    public static function connectDatabaseServer(string $dsn, string $username, string $password, string $dbName)
    {
        try
        {
            self::$connection = new PDO($dsn, $username, $password, self::$databaseInitOptions);

            $sql_query = "CREATE DATABASE IF NOT EXISTS $dbName";
    
            self::$connection->exec($sql_query);
            
            $sql_query = "USE $dbName";
    
            self::$connection->exec($sql_query);
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
        $sql_query = "CREATE DATABASE IF NOT EXISTS $dbName";

        self::$connection->exec($sql_query);
        
        $sql_query = "USE $dbName";

        self::$connection->exec($sql_query);   
    }

    public static function deleteDatabase(string $dbName)
    {
        $sql_query = "DROP DATABASE IF EXISTS $dbName";

        try
        {
            self::$connection->exec($sql_query); 
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
        }
    }

    //  CRUD STATEMENTS FOR A TABLE:

    public static function createTable(string $tableName, array|string $rowValues, int $primary_key_index, bool $createFromInputArray)
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

            try
            {
                self::$connection->exec($sql_query);
            }catch(Error $e)
            {
                echo "Nastala chyba: " . $e->getMessage() . "<br>";   
            }
    }

    public static function readTable(string $tableName, array $columnName = [], array $rowValues = [[]]): array
    {             
        if(count($columnName) && self::isMultiDimArrayEmpty($rowValues) && count($columnName) == count($rowValues))
        {   
            $sql_query = self::iterateMultiDimArray("SELECT * FROM $tableName WHERE ", $columnName, $rowValues);

        }else{
            $sql_query = "SELECT * FROM $tableName";
        } 
        
        try
        {
            $statement = self::$connection->query($sql_query);
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
        foreach ($rowValues as $rowValue)
            {
                $inputValuesArrayKeys = array_keys($rowValue);
            }
            
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
                $sql_query .= ":$inputValuesArrayKeys[$i]";
                if ($i < count($inputValuesArrayKeys)-1){
                    $sql_query .= ",";
                }
            }
    
            $sql_query .= ") ";

            try
            {
                $statement = self::$connection->prepare($sql_query);
    
                foreach($rowValues as $rowValue)
                { 
                    for ($i = 0; $i < count($inputValuesArrayKeys); $i++){
                            
                            $statement->bindParam(':' . $inputValuesArrayKeys[$i],$rowValue[$inputValuesArrayKeys[$i]]);
                    }
    
                    $statement->execute();    
                }  
            }catch(Error $e)
            {
                echo "Nastala chyba: " . $e->getMessage() . "<br>";
            }
    }

    public static function deleteTable(string $tableName, array $columnName = [], array $rowValues = [[]])
    {       
        if(count($columnName) && self::isMultiDimArrayEmpty($rowValues) && count($columnName) == count($rowValues))
        {
            $sql_query = self::iterateMultiDimArray("DELETE FROM $tableName WHERE ", $columnName, $rowValues);

        }else{
            $sql_query = "DROP TABLE IF EXISTS $tableName";
        } 
        
        try
        {     
            self::$connection->exec($sql_query);   
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
        }
    }

    //  CRUD STATEMENTS FOR ONE ROW:

    public static function insertOneRowInTable(string $tableName, array $rowValue)
    {
        $rowValueArrayKeys = array_keys($rowValue);     
        
        $sql_query = "INSERT INTO $tableName(";

        for ($i = 0; $i < count($rowValueArrayKeys); $i++){
            $sql_query .= "$rowValueArrayKeys[$i]";
            if ($i < count($rowValueArrayKeys)-1){
                $sql_query .= ",";
            }
        }

        $sql_query .= ") ";
        $sql_query .= "VALUES(";

        for ($i = 0; $i < count($rowValueArrayKeys); $i++){
            if(is_int($rowValue[$rowValueArrayKeys[$i]])){
                $sql_query .= $rowValue[$rowValueArrayKeys[$i]];
            }else{
                $sql_query .= "'" . $rowValue[$rowValueArrayKeys[$i]] . "'";
            }
            
            if ($i < count($rowValueArrayKeys)-1){
                $sql_query .= ",";
            }
        }

        $sql_query .= ")";
        
        try
        {
            self::$connection->exec($sql_query);
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";   
        }
    }

    public static function updateOneRowOneValueInTable(string $tableName, int|string $rowValue, string $columnName, int|string $valueToUpdate)
    {
        $columnNameWithPrimaryKey = self::getColumnWithPrimaryKey($tableName);
        
        $sql_query = "UPDATE $tableName SET $columnName = $valueToUpdate WHERE $columnNameWithPrimaryKey = $rowValue";
        
        try
        {
            self::$connection->exec($sql_query);         
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
        }
    }

    public static function readOneRowInTable(string $tableName, int|string $rowValue): array
    {
        $columnNameWithPrimaryKey = self::getColumnWithPrimaryKey($tableName);

        $sql_query = "SELECT * FROM $tableName WHERE $columnNameWithPrimaryKey = $rowValue";

        $statement = self::$connection->query($sql_query);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row;
    }

    public static function deleteOneRowInTable(string $tableName, int|string $rowValue)
    {
        $columnNameWithPrimaryKey = self::getColumnWithPrimaryKey($tableName);

        $sql_query = "DELETE FROM $tableName WHERE $columnNameWithPrimaryKey = $rowValue";

        try
        {
            self::$connection->exec($sql_query);         
        }catch(Error $e)
        {
            echo "Nastala chyba: " . $e->getMessage() . "<br>";
        }
    }
}