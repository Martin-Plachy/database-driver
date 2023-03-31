<?php

require_once 'config.php';
require_once 'databasedriver.php';

$dbName = "database0Name";
$tableName = "table0Name";

$rowTableInputArray = [
    'property0' => 0,
    'property1' => 1,
    'property2' => 2,
    'property3' => 3,
    'property4' => 4,
];

$rowTableInputArrayKeys = array_keys($rowTableInputArray);

$primary_key_index = 0;

DatabaseDriver::connectDatabaseServer($dsn, $username, $password, $dbName);
DatabaseDriver::createTable($tableName, $rowTableInputArray, $primary_key_index, true);
DatabaseDriver::insertOneRowInTable($tableName, $rowTableInputArray);
DatabaseDriver::disconnectDatabaseServer();
