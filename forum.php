<?php
session_start();
include('db.php');
include('navbar.php');


$query = "SELECT q.id, q.question_title, q.question_body, q.created_at, u.fullname FROM questions q JOIN users u ON q.user_id = u.id ORDER BY q.created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forum - Sage</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h2>Forum</h2>
    <?php while ($q = $result->fetch_assoc()): ?>
        <div class="question">
            <h3><?php echo htmlspecialchars($q['question_title']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($q['question_body'])); ?></p>
            <small>Asked by: <?php echo htmlspecialchars($q['fullname']); ?> on <?php echo $q['created_at']; ?></small>
            <form method="post" action="submit_answer.php">
                <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                <textarea name="answer" placeholder="Your answer"></textarea>
                <button type="submit">Submit Answer</button>
            </form>
            <?php
            $answers = $conn->prepare("SELECT a.answer, a.created_at, u.fullname, u.role FROM answers a JOIN users u ON a.user_id = u.id WHERE a.question_id = ?");
            $answers->bind_param("i", $q['id']);
            $answers->execute();
            $res = $answers->get_result();
            while ($a = $res->fetch_assoc()): ?>
                <div class="answer">
                    <p><?php echo nl2br(htmlspecialchars($a['answer'])); ?></p>
                    <small><?php echo htmlspecialchars($a['fullname']) . " (" . $a['role'] . ") on " . $a['created_at']; ?></small>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endwhile; ?>
</body>
</html>