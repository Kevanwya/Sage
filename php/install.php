<?php

if (isset($_SERVER['REQUEST_METHOD'])) {
    echo "<h1>Sage Platform Installation</h1>";
}

include_once 'create_folders.php';

include_once 'config.php';

echo "<h2>Installation Completed!</h2>";
echo "<p>The Sage platform has been installed successfully. You can now:</p>";
echo "<ul>";
echo "<li><a href='register.php'>Create an account</a></li>";
echo "<li><a href='login.php'>Log in to an existing account</a></li>";
echo "<li><a href='../index.html'>Return to the homepage</a></li>";
echo "</ul>";
?>
 