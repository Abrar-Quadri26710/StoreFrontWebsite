<?php
try {
    // 1. Connect to the local SQLite file (it will create it if it doesn't exist)
    $pdo = new PDO("sqlite:" . __DIR__ . "/database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Read the SQL commands from your setup file
    $sql = file_get_contents(__DIR__ . "/sqlite_setup.sql");
    
    // 3. Execute the commands to forge the tables and insert data
    $pdo->exec($sql);
    
    echo "Mission accomplished: Database forged successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>