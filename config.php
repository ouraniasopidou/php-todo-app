<?php

// my db connection file
// server name from SQL Server Management Studio

$db_server = 'RANIA\SQLEXPRESS05';
$db_name = 'todo_app';

function getDB() {
    static $pdo = null;
    
    // only connect once
    if ($pdo === null) {
        global $db_server, $db_name;
        
        $dsn = 'sqlsrv:Server=' . $db_server . ';Database=' . $db_name . ';TrustServerCertificate=1';
        
        try {
            $pdo = new PDO($dsn, null, null);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // TODO: make this look better later
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}