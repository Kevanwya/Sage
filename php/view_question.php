<?php
session_start();

if(!isset($_SESSION["loggedin"])  || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

if(!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: forum.php");
    exit;
}

$question_id = clean($conn, $_GET["id"]);
$user_id = $_SESSION["id"];
$user_type = $_SESSION["user_type"];

$question_sql = "SELECT q.*, u.username, u.user_type, u.full_name
                FROM questions q
                JOIN users u ON q.user_id = u.id
                WHERE q.id = ?";
                
$question_stmt = mysqli_prepare($conn, $question_sql);
mysqli_stmt_bind_param($question_stmt, "i", $question_id);
mysqli_stmt_execute($question_stmt);
$question_result = mysqli_stmt_get_result($question_stmt);

if(mysqli_num_rows($question_result) == 0) {
    header("location: forum.php");
    exit;
}

$question = mysqli_fetch_assoc($question_result);

$answers_sql = "SELECT a.*, u.username, u.user_type, u.full_name
               FROM answers a
               JOIN users u ON a.user_id = u.id
               WHERE a.question_id = ?
               ORDER BY a.is_best_answer DESC, a.created_at ASC";
               
$answers_stmt = mysqli_prepare($conn, $answers_sql);
mysqli_stmt_bind_param($answers_stmt, "i", $question_id);
mysqli_stmt_execute($answers_stmt);
$answers_result = mysqli_stmt_get_result($answers_stmt);

$answer_content = "";
$answer_err = "";
$success_msg = "";

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_answer"])) {
    
    if(empty(trim($_POST["answer_content"]))) {
        $answer_err = "Please enter your answer.";
    } else {
        $answer_content = clean($conn, trim($_POST["answer_content"]));
    }
    
    if(empty($answer_err)) {
        $insert_sql = "INSERT INTO answers (question_id, user_id, content) VALUES (?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "iis", $question_id, $user_id, $answer_content);
        
        if(mysqli_stmt_execute($insert_stmt)) {
            $success_msg = "Your answer has been posted successfully!";
            $answer_content = "";
            
            mysqli_stmt_execute($answers_stmt);
            $answers_result = mysqli_stmt_get_result($answers_stmt);
        } else {
            $answer_err = "Something went wrong. Please try again later.";
        }
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["mark_best_answer"])) {
    $answer_id = clean($conn, $_POST["answer_id"]);
    
    $reset_sql = "UPDATE answers SET is_best_answer = 0 WHERE question_id = ?";
    $reset_stmt = mysqli_prepare($conn, $reset_sql);
    mysqli_stmt_bind_param($reset_stmt, "i", $question_id);
    mysqli_stmt_execute($reset_stmt);
    
    $best_sql = "UPDATE answers SET is_best_answer = 1 WHERE id = ? AND question_id = ?";
    $best_stmt = mysqli_prepare($conn, $best_sql);
    mysqli_stmt_bind_param($best_stmt, "ii", $answer_id, $question_id);
    
    if(mysqli_stmt_execute($best_stmt)) {
        $resolve_sql = "UPDATE questions SET is_resolved = 1 WHERE id = ?";
        $resolve_stmt = mysqli_prepare($conn, $resolve_sql);
        mysqli_stmt_bind_param($resolve_stmt, "i", $question_id);
        mysqli_stmt_execute($resolve_stmt);
        
        $success_msg = "Answer marked as best! Question is now resolved.";
        $question['is_resolved'] = 1;
        
        mysqli_stmt_execute($answers_stmt);
        $answers_result = mysqli_stmt_get_result($answers_stmt);
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reopen_question"])) {
    $reset_sql = "UPDATE answers SET is_best_answer = 0 WHERE question_id = ?";
    $reset_stmt = mysqli_prepare($conn, $reset_sql);
    mysqli_stmt_bind_param($reset_stmt, "i", $question_id);
    mysqli_stmt_execute($reset_stmt);
    
    $unresolve_sql = "UPDATE questions SET is_resolved = 0 WHERE id = ?";
    $unresolve_stmt = mysqli_prepare($conn, $unresolve_sql);
    mysqli_stmt_bind_param($unresolve_stmt, "i", $question_id);
    
    if(mysqli_stmt_execute($unresolve_stmt)) {
        $success_msg = "Question has been reopened.";
        $question['is_resolved'] = 0;
        
        mysqli_stmt_execute($answers_stmt);
        $answers_result = mysqli_stmt_get_result($answers_stmt);
    }
}

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
    <link rel="stylesheet" href="../css/view_question.css">
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
        }
        
        .modal-content {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            padding: 20px;
        }
        
        .modal-image {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }
        
        .close:hover {
            color: #bbb;
        }
        
        /* Attachment styles */
        .attachment-link {
            position: relative;
            display: inline-block;
            padding: 8px 15px 8px 32px;
            background-color: #f0f9ff;
            margin-right: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
            cursor: pointer;
        }
        
        .attachment-link:before {
            content: "";
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%230ea5e9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l8.49-8.49a4 4 0 015.66 5.66l-7.78 7.78a2 2 0 01-2.83-2.83l6.37-6.37"></path></svg>');
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
        }
        
        .attachment-link:hover {
            background-color: #dbeafe;
        }
        
        .image-attachment {
            border: 2px solid #e0f2fe;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            max-width: 150px;
            max-height: 150px;
            display: inline-block;
        }
        
        .image-attachment img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .image-attachment:hover img {
            transform: scale(1.05);
        }
    </style>
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
                    <li><a href="my_sessions.php">My Sessions</a></li>
                    <?php } else { ?>
                    <li><a href="my_students.php">My Students</a></li>
                    <li><a href="schedule.php">My Schedule</a></li>
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
                    <div class="attachments-list">
                        <?php while($attachment = mysqli_fetch_assoc($attachments_result)): 
                            $file_extension = pathinfo($attachment['file_name'], PATHINFO_EXTENSION);
                            $is_image = in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
                        ?>
                            <?php if($is_image): ?>
                                <div class="image-attachment" onclick="openModal('<?php echo htmlspecialchars($attachment['file_path']); ?>')">
                                    <img src="<?php echo htmlspecialchars($attachment['file_path']); ?>" alt="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                </div>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" class="attachment-link" download>
                                    <?php echo htmlspecialchars($attachment['file_name']); ?>
                                </a>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
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
    
    <!-- Modal for image preview -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <div class="modal-content">
            <img id="modalImage" class="modal-image" src="" alt="Enlarged attachment">
        </div>
    </div>
    
    <script>
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = "block";
            modalImg.src = imageSrc;
        }
        
        function closeModal() {
            document.getElementById('imageModal').style.display = "none";
        }
        
        // Close modal when clicking outside the image
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    <script src="../js/main.js"></script>
</body>
</html>
 