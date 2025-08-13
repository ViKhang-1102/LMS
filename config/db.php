<?php
if (!isset($_SESSION)) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root'); 
define('DB_PASS', ''); 
define('DB_NAME', 'lms_db'); 

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
 
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>