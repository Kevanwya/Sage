<?php
session_start();
require_once '../logic/db.php';  // Include the database connection file

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    die("Unauthorized access.");
}

// Fetch all available tutors from the database
$query = "SELECT user_id, first_name, last_name FROM users WHERE role = 'tutor'";
$result = mysqli_query($conn, $query);
$tutors = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tutor_id = $_POST['tutor_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    // Insert booking into the database
    $user_id = $_SESSION['user_id']; // Get the logged-in student's user ID
    $query = "INSERT INTO bookings (student_id, tutor_id, date, time) VALUES ('$user_id', '$tutor_id', '$date', '$time')";
    
    if (mysqli_query($conn, $query)) {
        echo "Booking successfully submitted!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Sage - Submit Booking</title>
</head>
<body>
    <h1>Submit a Booking</h1>
    <form method="POST" action="submit_booking.php">
        <label for="tutor_id">Select Tutor:</label>
        <select name="tutor_id" id="tutor_id" required>
            <?php foreach ($tutors as $tutor) { ?>
                <option value="<?= $tutor['user_id'] ?>"><?= $tutor['first_name'] . ' ' . $tutor['last_name'] ?></option>
            <?php } ?>
        </select>
        <br><br>
        
        <label for="date">Select Date:</label>
        <input type="date" name="date" required>
        <br><br>

        <label for="time">Select Time:</label>
        <input type="time" name="time" required>
        <br><br>

        <button type="submit">Submit Booking</button>
    </form>
</body>
</html>

