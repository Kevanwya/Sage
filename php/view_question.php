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

// Check if question ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: forum.php");
    exit;
}

$question_id = clean($conn, $_GET["id"]);
$user_id = $_SESSION["id"];
$user_type = $_SESSION["user_type"];

// Get question details
$question_sql = "SELECT q.*, u.username, u.user_type, u.full_name
                FROM questions q
                JOIN users u ON q.user_id = u.id
                WHERE q.id = ?";
                
$question_stmt = mysqli_prepare($conn, $question_sql);
mysqli_stmt_bind_param($question_stmt, "i", $question_id);
mysqli_stmt_execute($question_stmt);
$question_result = mysqli_stmt_get_result($question_stmt);

// Check if question exists
if(mysqli_num_rows($question_result) == 0) {
    header("location: forum.php");
    exit;
}

$question = mysqli_fetch_assoc($question_result);

// Get answers for the question
$answers_sql = "SELECT a.*, u.username, u.user_type, u.full_name
               FROM answers a
               JOIN users u ON a.user_id = u.id
               WHERE a.question_id = ?
               ORDER BY a.is_best_answer DESC, a.created_at ASC";
               
$answers_stmt = mysqli_prepare($conn, $answers_sql);
mysqli_stmt_bind_param($answers_stmt, "i", $question_id);
mysqli_stmt_execute($answers_stmt);
$answers_result = mysqli_stmt_get_result($answers_stmt);

// Process new answer submission
$answer_content = "";
$answer_err = "";
$success_msg = "";

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_answer"])) {
    
    // Validate answer content
    if(empty(trim($_POST["answer_content"]))) {
        $answer_err = "Please enter your answer.";
    } else {
        $answer_content = clean($conn, trim($_POST["answer_content"]));
    }
    
    // If no errors, insert answer
    if(empty($answer_err)) {
        $insert_sql = "INSERT INTO answers (question_id, user_id, content) VALUES (?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "iis", $question_id, $user_id, $answer_content);
        
        if(mysqli_stmt_execute($insert_stmt)) {
            $success_msg = "Your answer has been posted successfully!";
            $answer_content = ""; // Clear the form
            
            // Refresh the answers list
            mysqli_stmt_execute($answers_stmt);
            $answers_result = mysqli_stmt_get_result($answers_stmt);
        } else {
            $answer_err = "Something went wrong. Please try again later.";
        }
    }
}

// Process marking as best answer
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["mark_best_answer"])) {
    $answer_id = clean($conn, $_POST["answer_id"]);
    
    // First reset all answers for this question
    $reset_sql = "UPDATE answers SET is_best_answer = 0 WHERE question_id = ?";
    $reset_stmt = mysqli_prepare($conn, $reset_sql);
    mysqli_stmt_bind_param($reset_stmt, "i", $question_id);
    mysqli_stmt_execute($reset_stmt);
    
    // Then set the selected answer as best
    $best_sql = "UPDATE answers SET is_best_answer = 1 WHERE id = ? AND question_id = ?";
    $best_stmt = mysqli_prepare($conn, $best_sql);
    mysqli_stmt_bind_param($best_stmt, "ii", $answer_id, $question_id);
    
    if(mysqli_stmt_execute($best_stmt)) {
        // Mark question as resolved
        $resolve_sql = "UPDATE questions SET is_resolved = 1 WHERE id = ?";
        $resolve_stmt = mysqli_prepare($conn, $resolve_sql);
        mysqli_stmt_bind_param($resolve_stmt, "i", $question_id);
        mysqli_stmt_execute($resolve_stmt);
        
        $success_msg = "Answer marked as best! Question is now resolved.";
        $question['is_resolved'] = 1;
        
        // Refresh the answers list
        mysqli_stmt_execute($answers_stmt);
        $answers_result = mysqli_stmt_get_result($answers_stmt);
    }
}

// Process reopening question
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reopen_question"])) {
    // Reset all best answers
    $reset_sql = "UPDATE answers SET is_best_answer = 0 WHERE question_id = ?";
    $reset_stmt = mysqli_prepare($conn, $reset_sql);
    mysqli_stmt_bind_param($reset_stmt, "i", $question_id);
    mysqli_stmt_execute($reset_stmt);
    
    // Mark question as unresolved
    $unresolve_sql = "UPDATE questions SET is_resolved = 0 WHERE id = ?";
    $unresolve_stmt = mysqli_prepare($conn, $unresolve_sql);
    mysqli_stmt_bind_param($unresolve_stmt, "i", $question_id);
    
    if(mysqli_stmt_execute($unresolve_stmt)) {
        $success_msg = "Question has been reopened.";
        $question['is_resolved'] = 0;
        
        // Refresh the answers list
        mysqli_stmt_execute($answers_stmt);
        $answers_result = mysqli_stmt_get_result($answers_stmt);
    }
}

// Get attachments for the question
$attachments_sql = "SELECT * FROM attachments WHERE question_id = ?";
$attachments_stmt = mysqli_prepare($conn, $attachments_sql);
mysqli_stmt_bind_param($attachments_stmt, "i", $question_id);
mysqli_stmt_execute($attachments_stmt);
$attachments_result = mysqli_stmt_get_result($attachments_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum</title>
    <link rel="stylesheet" href="../css/view_question.css">
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
                    <li><a href="forum.php" class="active">Q&A Forum</a></li>
                    <?php if($_SESSION["user_type"] == 'student') { ?>
                    <li><a href="tutors.php">Find Tutors</a></li>
                    <li><a href="my_sessions.php">Sessions</a></li>
                    <?php } else { ?>
                    <li><a href="my_students.php">Students</a></li>
                    <li><a href="schedule.php">Schedule</a></li>
                    <?php } ?>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Question Details</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success">
                <?php echo $success_msg; ?>
            </div>
            <?php endif; ?>
            
            <div class="question-container">
                <div class="question-header">
                    <div class="question-meta">
                        <span class="question-subject"><?php echo htmlspecialchars($question['subject']); ?></span>
                        <span class="question-status <?php echo $question['is_resolved'] ? 'status-resolved' : 'status-unresolved'; ?>">
                            <?php echo $question['is_resolved'] ? 'Resolved' : 'Unresolved'; ?>
                        </span>
                    </div>
                    <h2 class="question-title"><?php echo htmlspecialchars($question['title']); ?></h2>
                    <div class="question-info">
                        <div class="author-info">
                            <span class="author-name"><?php echo htmlspecialchars($question['full_name']); ?></span>
                            <span class="author-type">(<?php echo ucfirst($question['user_type']); ?>)</span>
                        </div>
                        <div class="question-date">
                            Asked on <?php echo date('M j, Y \a\t g:i A', strtotime($question['created_at'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="question-content">
                    <?php echo nl2br(htmlspecialchars($question['content'])); ?>
                </div>
                
                <?php if(mysqli_num_rows($attachments_result) > 0): ?>
                <div class="question-attachments">
                    <h3>Attachments</h3>
                    <ul class="attachments-list">
                        <?php while($attachment = mysqli_fetch_assoc($attachments_result)): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" class="attachment-link">
                                    <?php echo htmlspecialchars($attachment['file_name']); ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if($question['user_id'] == $user_id && !$question['is_resolved']): ?>
                    <div class="question-actions">
                        <a href="edit_question.php?id=<?php echo $question_id; ?>" class="btn btn-secondary">Edit Question</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="answers-section">
                <h2 class="section-title">
                    <?php echo mysqli_num_rows($answers_result); ?> 
                    <?php echo mysqli_num_rows($answers_result) == 1 ? 'Answer' : 'Answers'; ?>
                </h2>
                
                <?php if(mysqli_num_rows($answers_result) > 0): ?>
                    <div class="answers-list">
                        <?php while($answer = mysqli_fetch_assoc($answers_result)): ?>
                            <div class="answer-item <?php echo $answer['is_best_answer'] ? 'best-answer' : ''; ?>">
                                <?php if($answer['is_best_answer']): ?>
                                    <div class="best-answer-badge">Best Answer</div>
                                <?php endif; ?>
                                
                                <div class="answer-meta">
                                    <div class="author-info">
                                        <span class="author-name"><?php echo htmlspecialchars($answer['full_name']); ?></span>
                                        <span class="author-type">(<?php echo ucfirst($answer['user_type']); ?>)</span>
                                    </div>
                                    <div class="answer-date">
                                        Answered on <?php echo date('M j, Y \a\t g:i A', strtotime($answer['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div class="answer-content">
                                    <?php echo nl2br(htmlspecialchars($answer['content'])); ?>
                                </div>
                                
                                <?php if($question['user_id'] == $user_id && !$answer['is_best_answer'] && !$question['is_resolved']): ?>
                                    <div class="answer-actions">
                                        <form method="post">
                                            <input type="hidden" name="answer_id" value="<?php echo $answer['id']; ?>">
                                            <button type="submit" name="mark_best_answer" class="btn btn-primary">Mark as Best Answer</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-answers">
                        <p>No answers yet. Be the first to answer this question!</p>
                    </div>
                <?php endif; ?>
                
                <?php if($question['is_resolved'] && $question['user_id'] == $user_id): ?>
                    <div class="question-actions">
                        <form method="post">
                            <button type="submit" name="reopen_question" class="btn btn-secondary">Reopen Question</button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <?php if(!$question['is_resolved'] || $user_type == 'tutor'): ?>
                    <div class="post-answer">
                        <h2 class="section-title">Your Answer</h2>
                        <form method="post" class="answer-form">
                            <div class="form-group">
                                <textarea name="answer_content" class="form-control <?php echo (!empty($answer_err)) ? 'is-invalid' : ''; ?>" rows="6" placeholder="Type your answer here..."><?php echo $answer_content; ?></textarea>
                                <span class="invalid-feedback"><?php echo $answer_err; ?></span>
                            </div>
                            <button type="submit" name="submit_answer" class="btn btn-primary">Post Answer</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="back-link">
                <a href="forum.php" class="btn btn-back">Back to Q&A Forum</a>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 