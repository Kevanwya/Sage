<?php
require_once '../logic/db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $tutor = trim(mysqli_real_escape_string($conn, $_POST['tutor']));
    $date = $_POST['date'];
    $time = $_POST['time'];

    if (empty($name) || empty($email) || empty($tutor) || empty($date) || empty($time)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("INSERT INTO bookings (name, email, tutor, date, time) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $tutor, $date, $time);

        if ($stmt->execute()) {
            $success = "Booking successful!";
        } else {
            $error = "Error saving booking. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sage - Book a Session</title>
</head>
<body>

<h2>Book a Tutoring Session</h2>

<?php 
    if($error) echo "<p style='color:red;'>$error</p>"; 
?>

<?php 
    if($success) echo "<p style='color:green;'>$success</p>";
?>

<form method="POST" action="">
    Name: <input type="text" name="name" required><br>
    Email: <input type="email" name="email" required><br>
    Tutor: <input type="text" name="tutor" required><br>
    Date: <input type="date" name="date" required><br>
    Time: <input type="time" name="time" required><br>
    <input type="submit" value="Book Session">
</form>

</body>
</html>
