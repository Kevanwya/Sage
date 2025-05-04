<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'sage');

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

if (!$conn) {
    die("ERROR: Could not connect to MySQL. " . mysqli_connect_error());
}

$sql_file = file_get_contents('../sql/database.sql');

$statements = explode(';', $sql_file);

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        if (!mysqli_query($conn, $statement)) {
            die("ERROR: Failed to execute SQL statement: " . mysqli_error($conn));
        }
    }
}

mysqli_select_db($conn, DB_NAME);

function clean($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}
?>
 
 