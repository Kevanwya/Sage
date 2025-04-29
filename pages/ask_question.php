<?php
session_start();
include('../logic/db.php');
include('navbar.php');


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $body = $_POST['body'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO questions (user_id, question_title, question_body) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $title, $body);
    $stmt->execute();
    header("Location: forum.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ask Question - Sage</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h2>Ask a Question</h2>
    <form method="post" action="ask_question.php">
        <input type="text" name="title" placeholder="Question Title" required>
        <textarea name="body" placeholder="Describe your question" required></textarea>
        <button type="submit">Submit</button>
    </form>
</body>
</html>