<?php
//   Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'sage');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if (!$conn) {
    die("ERROR: Could not connect to MySQL. " . mysqli_connect_error());
}

// Import SQL file for database creation
$sql_file = file_get_contents('../sql/database.sql');

// Split SQL file into individual statements
$statements = explode(';', $sql_file);

// Execute each statement
foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        if (!mysqli_query($conn, $statement)) {
            die("ERROR: Failed to execute SQL statement: " . mysqli_error($conn));
        }
    }
}

// Select the database after creation
mysqli_select_db($conn, DB_NAME);

// Function to protect against SQL injection
function clean($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}
?>
 
 