<?php
session_start();
include('navbar.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Sage</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h1>
    <p>You are logged in as a <?php echo htmlspecialchars($_SESSION['role']); ?>.</p>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="ask_question.php">Ask a Question</a>
        <a href="forum.php">View Forum</a>
        <a href="logout.php">Logout</a>
    </nav>
</body>
</html>
