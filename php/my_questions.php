<?php
// Initialize session
session_start();

// Check login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

// Pagination setup
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;
$user_id = $_SESSION["id"];

// Total question count
$count_sql = "SELECT COUNT(*) as total FROM questions WHERE user_id = ?";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $user_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_results = $count_row['total'];
$total_pages = ceil($total_results / $results_per_page);

// Fetch questions
$sql = "SELECT * FROM questions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?, ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $offset, $results_per_page);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$questions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $questions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions</title>
    <link rel="stylesheet" href="../css/my_answers.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Sage</h2>
                <p class="user-welcome">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="forum.php">Q&A Forum</a></li>
                    <?php if($_SESSION["user_type"] == 'student') { ?>
                    <li><a href="tutors.php">Find Tutors</a></li>
                    <li><a href="my_sessions.php">Sessions</a></li>
                    <?php } else { ?>
                    <li><a href="my_students.php">Students</a></li>
                    <li><a href="schedule.php">Schedule</a></li>
                    <?php } ?>
                    <li><a href="my_questions.php" class="active">Questions</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>My Questions</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>

            <div class="answers-container">
                <div class="answers-header">
                    <h2>Your Questions (<?php echo $total_results; ?>)</h2>
                    <a href="ask_question.php" class="btn btn-primary">Ask a New Question</a>
                </div>

                <?php if (count($questions) > 0): ?>
                    <div class="answers-list">
                        <?php foreach ($questions as $question): ?>
                            <div class="answer-item <?php echo $question['is_resolved'] ? 'status-resolved' : 'status-unresolved'; ?>">
                                <div class="answer-meta">
                                    <div class="answer-stats">
                                        <span class="answer-subject"><?php echo htmlspecialchars($question['subject']); ?></span>
                                        <span class="question-status <?php echo $question['is_resolved'] ? 'status-resolved' : 'status-unresolved'; ?>">
                                            <?php echo $question['is_resolved'] ? 'Resolved' : 'Unresolved'; ?>
                                        </span>
                                    </div>
                                    <div class="answer-date">
                                        Asked on <?php echo date('M j, Y \a\t g:i A', strtotime($question['created_at'])); ?>
                                    </div>
                                </div>

                                <h3 class="question-title">
                                    <a href="view_question.php?id=<?php echo $question['id']; ?>">
                                        <?php echo htmlspecialchars($question['title']); ?>
                                    </a>
                                </h3>

                                <div class="answer-content">
                                    <?php echo nl2br(htmlspecialchars(substr($question['content'], 0, 250))); ?>
                                    <?php if(strlen($question['content']) > 250): ?>
                                        <span class="read-more">... <a href="view_question.php?id=<?php echo $question['id']; ?>">Read more</a></span>
                                    <?php endif; ?>
                                </div>

                                <div class="answer-actions">
                                    <a href="view_question.php?id=<?php echo $question['id']; ?>" class="btn btn-secondary">View Full Question</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="page-link">&laquo; Previous</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="page-link current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="page-link">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-answers">
                        <p>You haven't asked any questions yet.</p>
                        <a href="ask_question.php" class="btn btn-primary">Ask Your First Question</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
