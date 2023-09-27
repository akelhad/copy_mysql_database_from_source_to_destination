<?php


// source database connection details
$sourceHost = 'localhost';
$sourceUser = 'root';
$sourcePassword = '';
$sourceDatabase = 'sourcedb';

// Destination database connection details
$destHost = 'localhost';
$destUser = 'root';
$destPassword = '';
$destDatabase = 'destinationdb';


try {
    // Create source database connection
    $sourceConn = new PDO("mysql:host=$sourceHost;dbname=$sourceDatabase", $sourceUser, $sourcePassword);

    // Create destination database connection
    $destConn = new PDO("mysql:host=$destHost;dbname=$destDatabase", $destUser, $destPassword);

    // Set error mode to exception
    $sourceConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $destConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch table names from the source database
    $tableQuery = "SHOW TABLES";
    $tableStatement = $sourceConn->query($tableQuery);
    $tables = $tableStatement->fetchAll(PDO::FETCH_COLUMN);

    // Migrate data for each table
    foreach ($tables as $table) {
        $dropTableQuery = "DROP TABLE IF EXISTS $table";
        $destConn->exec($dropTableQuery);
    
        // Get the create table statement for the source table
        $createTableQuery = "SHOW CREATE TABLE $table";
        $createTableStatement = $sourceConn->query($createTableQuery)->fetchColumn(1);

        // Set destination database
        $destConn->exec("USE $destDatabase");

        // Create the table in the destination database
        $destConn->exec($createTableStatement);

        // Retrieve the data from the source table
        $selectQuery = "SELECT * FROM $table";
        $selectStatement = $sourceConn->query($selectQuery);

        // Insert the rows into the destination table
        $destConn->beginTransaction();
        while ($row = $selectStatement->fetch(PDO::FETCH_ASSOC)) {
           // $columns = implode(", ", array_keys($row));
            $columns = implode(", ", array_map(function ($column) {
    return "`$column`";
}, array_keys($row)));
            $values = implode(", ", array_fill(0, count($row), "?"));
            $insertQuery = "INSERT INTO $table ($columns) VALUES ($values)";
            $insertStatement = $destConn->prepare($insertQuery);
            $insertStatement->execute(array_values($row));
        }
        $destConn->commit();
    }

    // Close the connections
    $sourceConn = null;
    $destConn = null;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}


?>