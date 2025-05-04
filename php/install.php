<?php
//  Sage Installation and Setup Script

// Check if script is running via web browser
if (isset($_SERVER['REQUEST_METHOD'])) {
    echo "<h1>Sage Platform Installation</h1>";
}

// Create necessary folders
include_once 'create_folders.php';

// Create database if it doesn't exist
include_once 'config.php';

echo "<h2>Installation Completed!</h2>";
echo "<p>The Sage platform has been installed successfully. You can now:</p>";
echo "<ul>";
echo "<li><a href='register.php'>Create an account</a></li>";
echo "<li><a href='login.php'>Log in to an existing account</a></li>";
echo "<li><a href='../index.html'>Return to the homepage</a></li>";
echo "</ul>";
?>
 