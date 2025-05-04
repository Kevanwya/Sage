<?php
//  This script creates necessary folders for uploads and SQL files

// Define the folders to create
$folders = [
    '../uploads',
    '../uploads/profile_images',
    '../uploads/question_attachments',
    '../uploads/answer_attachments',
    '../sql'
];

// Loop through each folder and create it if it doesn't exist
foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        // Create the directory with permissions
        if (mkdir($folder, 0755, true)) {
            echo "Created folder: " . $folder . "<br>";
        } else {
            echo "Failed to create folder: " . $folder . "<br>";
        }
    } else {
        echo "Folder already exists: " . $folder . "<br>";
    }
}

echo "<p>Folder creation completed.</p>";
echo "<p><a href='index.php'>Return to homepage</a></p>";
?>
 