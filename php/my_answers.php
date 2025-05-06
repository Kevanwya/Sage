<?php
//  Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Set up pagination
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

$user_id = $_SESSION["id"];

// Get total number of answers by this user
$count_sql = "SELECT COUNT(*) as total FROM answers WHERE user_id = ?";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $user_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_results = $count_row['total'];
$total_pages = ceil($total_results / $results_per_page);

// Get answers with question details
$sql = "SELECT a.*, q.title as question_title, q.subject, q.is_resolved, u.username as question_author
        FROM answers a
        JOIN questions q ON a.question_id = q.id
        JOIN users u ON q.user_id = u.id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC
        LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $offset, $results_per_page);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$answers = [];
while($row = mysqli_fetch_assoc($result)) {
    $answers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Answers</title>
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
                    <li><a href="my_answers.php" class="active">Answers</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>My Answers</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <div class="answers-container">
                <div class="answers-header">
                    <h2>Your Answers (<?php echo $total_results; ?>)</h2>
                    <a href="forum.php" class="btn btn-primary">Answer More Questions</a>
                </div>
                
                <?php if(count($answers) > 0): ?>
                    <div class="answers-list">
                        <?php foreach($answers as $answer): ?>
                            <div class="answer-item <?php echo $answer['is_best_answer'] ? 'best-answer' : ''; ?>">
                                <?php if($answer['is_best_answer']): ?>
                                    <div class="best-answer-badge">Best Answer</div>
                                <?php endif; ?>
                                
                                <div class="answer-meta">
                                    <div class="answer-stats">
                                        <span class="answer-subject"><?php echo htmlspecialchars($answer['subject']); ?></span>
                                        <span class="question-status <?php echo $answer['is_resolved'] ? 'status-resolved' : 'status-unresolved'; ?>">
                                            <?php echo $answer['is_resolved'] ? 'Resolved' : 'Unresolved'; ?>
                                        </span>
                                    </div>
                                    <div class="answer-date">
                                        Answered on <?php echo date('M j, Y \a\t g:i A', strtotime($answer['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <h3 class="question-title">
                                    <a href="view_question.php?id=<?php echo $answer['question_id']; ?>">
                                        <?php echo htmlspecialchars($answer['question_title']); ?>
                                    </a>
                                </h3>
                                
                                <p class="question-author">
                                    Asked by <?php echo htmlspecialchars($answer['question_author']); ?>
                                </p>
                                
                                <div class="answer-content">
                                    <?php echo nl2br(htmlspecialchars(substr($answer['content'], 0, 250))); ?>
                                    <?php if(strlen($answer['content']) > 250): ?>
                                        <span class="read-more">... <a href="view_question.php?id=<?php echo $answer['question_id']; ?>">Read more</a></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="answer-actions">
                                    <a href="view_question.php?id=<?php echo $answer['question_id']; ?>" class="btn btn-secondary">View Full Question</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="page-link">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if($i == $page): ?>
                                    <span class="page-link current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="page-link">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-answers">
                        <p>You haven't provided any answers yet.</p>
                        <a href="forum.php" class="btn btn-primary">Browse Questions to Answer</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 