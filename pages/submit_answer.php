<?php
session_start();
include('../logic/db.php');


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $answer = $_POST['answer'];
    $question_id = $_POST['question_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO answers (question_id, user_id, answer) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $question_id, $user_id, $answer);
    $stmt->execute();
    header("Location: forum.php");
    exit();
}
?>
