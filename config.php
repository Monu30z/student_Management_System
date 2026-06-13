<?php
// Database Configuration
// define('DB_HOST', 'sql211.infinityfree.com');
// define('DB_USER', 'if0_42144981');
// define('DB_PASS', 'ad9696492963');
// define('DB_NAME', 'if0_42144981_gpmau');
// define('DB_PORT', '3306');

$host = "localhost";
$user = "root";
$database = "Mycollege";
$password = null;

// Site Configuration
// define('SITE_URL', 'http://mystudentsystem.lovestoblog.com');
// define('SITE_NAME', 'Government Polytechnic Mau');

// Timezone
// date_default_timezone_set('Asia/Kolkata');

// Database Connection
try {
   $conn = mysqli_connect($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Start Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>