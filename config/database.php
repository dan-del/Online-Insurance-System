<?php

define('DB_SERVER', 'localhost'); // Your MySQL host
define('DB_USERNAME', 'root');   // Your MySQL username (default for XAMPP is 'root')
define('DB_PASSWORD', '');       // Your MySQL password (default for XAMPP is empty)
define('DB_NAME', 'online_insurance_db'); // The name of the database you just created

/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Optional: Set character set for the connection
mysqli_set_charset($link, "utf8mb4");

// You can optionally add a simple test message here for initial setup
// echo "Database connection successful!"; // Uncomment to test, then comment out

?>