<?php

/**
 * A simple database driver for a connection to MySQL database and sending basic SQL statements.
 * @package database-driver
 * @author Martin Plachý <martin.plachy86@gmail.com>
 */

class DatabaseDriver
{ 
    /**
    * @var PDO $connection PHP data object for connection to a database
    * @var string $dbName the name of a database
    */
    
    private string $dbName;

    /**
     * The private method returns a name of a column with PRIMARY KEY property
     * @param array $tableName a name of a table 
     * @throws Error if it was unable to find out a name of the column with PRIMARY KEY property 
     * @return string returns true if the connection was successful 
     */

    private function getColumnWithPrimaryKey(string $tableName): string
    {
        $sqlQuery = "SELECT K.COLUMN_NAME FROM  
        INFORMATION_SCHEMA.TABLE_CONSTRAINTS T
        JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE K
        ON K.CONSTRAINT_NAME=T.CONSTRAINT_NAME  
        WHERE K.TABLE_NAME='$tableName'
        AND T.CONSTRAINT_TYPE='PRIMARY KEY' LIMIT 1";
        
        try
        {
            $statement = $this->connection->query($sqlQuery);
            $columnNameWithPrimaryKeyArray = $statement->fetch(PDO::FETCH_ASSOC);
            return $columnNameWithPrimaryKeyArray['COLUMN_NAME'];        
        }catch(Error $e)
        {
            echo "Error: " . $e->getMessage() . "<br>";
            return "";
        }      
    }

    /**
     * The private method returns true if an input array is multidimensional
     * @param array $rowValues an input array to proccess
     * @return bool returns true if an input array is multidimensional
     */

    private function isMultiDimArrayEmpty($rowValues = []) : bool
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

    /**
     * The private method iterates $rowValues values in $columName column and returns a part of SQL statement
     * @param string $sqlQuery an input part of SQL statement
     * @param array $columnName a name of column to insert to SQL statement
     * @param array $rowValues values in a column to insert to SQL statement
     * @return string returns a part of SQL statement
     */

    private function iterateMultiDimArray(string $sqlQuery, $columnName = [], $rowValues = []): string
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

    public function __construct(private PDO $connection)
    {
    }

    /**
     * The public method handles the database server disconnection.
     * @throws PDOException if the database connection was unable to close 
     * @return bool returns true if the disconnection was successful 
     */

    public function disconnectDatabaseServer() : bool
    {
        try
        {
            $this->connection = null;
            return true;
        }catch(PDOException $e)
        {
            echo "Unable to disconnect the database server: " . $e->getMessage() . "<br>";
            return false;            
        }

    }

    /**
     * The public method handles the database creation
     * @param string $dbName the name of a new database
     * @throws Error if it was unable to create a new database 
     * @return bool returns true if a new database was created successfully 
     */

    public function createDatabase(string $dbName) : bool
    {
        try
        {
            $sqlQuery = "CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8 COLLATE utf8_czech_ci";
            $this->connection->exec($sqlQuery);            
            $sqlQuery = "USE $dbName";    
            $this->connection->exec($sqlQuery);            
            return true;
        }catch(Error $e)
        {
            echo "Error: " . $e->getMessage() . "<br>";
            return false;
        } 
    }

    /**
     * The public method handles the database deletion
     * @param string $dbName the name of the database to delete
     * @throws Error if it was unable to delete the database 
     * @return bool returns true if the database was deleted successfully 
     */

    public function deleteDatabase(string $dbName) : bool
    {
        try
        {
            $sqlQuery = "DROP DATABASE IF EXISTS $dbName";
            $this->connection->exec($sqlQuery);
            return true; 
        }catch(Error $e)
        {
            echo "Error: " . $e->getMessage() . "<br>";
            return false;
        }
    }

    /**
     * The public method handles a table creation
     * @param string $tableName the name of a new table
     * @param array|string $rowValues an input array to format a new table
     * @param int $primaryKeyIndex specifies which column of a table has the PRIMARY KEY property
     * @param bool $createFromInputString specifies whether an input is an array or string whith a SQL statement (false = array)
     * @throws Error if it was unable to create a table 
     * @return bool returns true if a table was created successfully 
     */

    public function createTable(string $tableName, array|string $rowValues, int $primaryKeyIndex, bool $createFromInputString = false) : bool
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
        
                    if ($i == $primaryKeyIndex){
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
                $this->connection->exec($sqlQuery);
                return true;
            }catch(Error $e)
            {
                echo "Error: " . $e->getMessage() . "<br>";
                return false;   
            }
    }

    /**
     * The public method reads a database table and returns it as an array
     * @param string $tableName the name of the table
     * @param array $columnName an array of column names to match
     * @param array $rowValues an array of column values to match
     * @param int $primaryKeyIndex specifies which column of a table has the PRIMARY KEY property
     * @param bool $createFromInputString specifies whether an input is an array or string whith a SQL statement (false = array)
     * @throws Error if it was unable to read a table 
     * @return array returns an array representing a table 
     */

    public function readTable(string $tableName, $columnName = [], $rowValues = [[]]): array
    {             
        if(count($columnName) && $this->isMultiDimArrayEmpty($rowValues) && count($columnName) == count($rowValues))
        {   
            $sqlQuery = $this->iterateMultiDimArray("SELECT * FROM $tableName WHERE ", $columnName, $rowValues);

        }else{
            $sqlQuery = "SELECT * FROM $tableName";
        } 
        
        try
        {
            $statement = $this->connection->query($sqlQuery);
            $table = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $table; 
        }catch(Error $e)
        {
            echo "Error: " . $e->getMessage() . "<br>";
            return [];
        }
    }

    /**
     * The public method inserts rows in a table
     * @param string $tableName specifies the name of the table
     * @param array $rowValues an array of column values to insert
     * @throws Error if it was unable to insert data 
     * @return bool returns true if values were inserted successfully 
     */

    public function insertRowsInTable(string $tableName, array $rowValues) : bool
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
            $sqlQueryPrepared = $this->connection->prepare($sqlQuery);
    
            foreach($rowValues as $rowValue)
            { 
                for ($i = 0; $i < count($inputValuesArrayKeys); $i++)
                {                            
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
            return true;  
        }catch(Error $e)
        {
            echo "Error: " . $e->getMessage() . "<br>";
            return false;
        }
    }

    /**
     * The public method deletes a table (part or entire)
     * @param string $tableName the name of the table
     * @param array $columnName an array of column names to delete (if not specified the entire table is deleted)
     * @param array $rowValues an array of column values to delete
     * @throws Error if it was unable to delete a table 
     * @return bool returns true if the table was deleted successfully 
     */
    
    public function deleteTable(string $tableName, $columnName = [], $rowValues = []) : bool
    {
        if(count($columnName) && $this->isMultiDimArrayEmpty($rowValues) && count($columnName) == count($rowValues))
        {
            $sqlQuery = $this->iterateMultiDimArray("DELETE FROM $tableName WHERE ", $columnName, $rowValues);
        }else{
            $sqlQuery = "DROP TABLE IF EXISTS $tableName";
        } 

        try
        {     
            $sqlQueryPrepared = $this->connection->prepare($sqlQuery);
            foreach ($rowValues as $keyI => $rowValue)
            {
                foreach ($rowValue as $keyJ => $value)
                {    
                    $sqlQueryPrepared->bindValue(':value_' . $keyI . '_' . $keyJ,$value);
                }
            }
            $sqlQueryPrepared->execute();
            return true; 
        }catch(Error $e)
        {
            echo "Error: " . $e->getMessage() . "<br>";
            return false;
        }
    }

    //  CRUD STATEMENTS FOR ONE ROW:

    /**
     * The public method inserts one row in a table
     * @param string $tableName the name of the table
     * @param array $rowValue an array of column values to insert
     * @throws Error if it was unable to insert a row 
     * @return bool returns true if a row was inserted in a table successfully 
     */

    public function insertOneRowInTable(string $tableName, $rowValue = []) : bool
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
            $sqlQueryPrepared = $this->connection->prepare($sqlQuery);

            for ($i = 0; $i < count($rowValueArrayKeys); $i++)
            {           
                $sqlQueryPrepared->bindParam(':' . $rowValueArrayKeys[$i],$rowValue[$rowValueArrayKeys[$i]]);
            }
            $sqlQueryPrepared->execute();
            return true;
        }catch(Error $e)
        {
            echo "Error: " . $e->getMessage() . "<br>";
            return false;
        }
    }

    /**
     * The public method updates one row in a table
     * @param string $tableName the name of the table
     * @param int|string $rowValue a value to update
     * @param string $columnName the name of the column where a value to be updated is placed
     * @param int|string $valueToUpdate a value to be updated
     * @throws Error if it was unable to update a value 
     * @return bool returns true if a value was updated successfully 
     */

    public function updateOneRowOneValueInTable(string $tableName, int|string $rowValue, string $columnName, int|string $valueToUpdate) : bool
    {
        $columnNameWithPrimaryKey = $this->getColumnWithPrimaryKey($tableName);      
        $sqlQuery = "UPDATE $tableName SET $columnName = :valueToUpdate WHERE $columnNameWithPrimaryKey = :rowValue";
        
        try
        {
            $sqlQueryPrepared = $this->connection->prepare($sqlQuery);
            $sqlQueryPrepared->bindParam(':valueToUpdate',$valueToUpdate);
            $sqlQueryPrepared->bindParam(':rowValue',$rowValue);
            $sqlQueryPrepared->execute();
            return true;      
        }catch(Error $e)
        {
            echo "Error: " . $e->getMessage() . "<br>";
            return false;
        }
    }

    /**
     * The public method reads one row in table and returns it as an array
     * @param string $tableName the name of the table
     * @param int|string $rowValue a value of a column with PRIMARY KEY
     * @throws Error if it was unable to read a row 
     * @return array returns an array representing a row of the table 
     */

    public function readOneRowInTable(string $tableName, int|string $rowValue): array
    {
        $columnNameWithPrimaryKey = $this->getColumnWithPrimaryKey($tableName);

        $sqlQuery = "SELECT * FROM $tableName WHERE $columnNameWithPrimaryKey = :rowValue";

        try
        {
            $sqlQueryPrepared = $this->connection->prepare($sqlQuery);
            $sqlQueryPrepared->bindParam(':rowValue',$rowValue);
            $sqlQueryPrepared->execute(); 
            $row = $sqlQueryPrepared->fetch(PDO::FETCH_ASSOC);
            return $row;
        }catch(Error $e)
        {
            echo "Error: " . $e->getMessage() . "<br>";
            return [];
        }   
    }

    /**
     * The public method deletes one row in a table
     * @param string $tableName the name of the table
     * @param int|string $rowValue a value of a column with PRIMARY KEY to be deleted
     * @throws Error if it was unable to delete a row of the table 
     * @return bool returns true if a row was deleted successfully 
     */

    public function deleteOneRowInTable(string $tableName, int|string $rowValue) : bool
    {
        $columnNameWithPrimaryKey = $this->getColumnWithPrimaryKey($tableName);

        $sqlQuery = "DELETE FROM $tableName WHERE $columnNameWithPrimaryKey = :rowValue";

        try
        {
            $sqlQueryPrepared = $this->connection->prepare($sqlQuery);
            $sqlQueryPrepared->bindParam(':rowValue',$rowValue);
            $sqlQueryPrepared->execute();
            return true;
        }catch(Error $e)
        {
            echo "Error: " . $e->getMessage() . "<br>";
            return false;
        }
    }
}
