<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

$user_type = $_SESSION["user_type"];
$username = $_SESSION["username"];
$user_id = $_SESSION["id"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sage</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Sage</h2>
                <p class="user-welcome">Welcome, <?php echo htmlspecialchars($username); ?> (Secondary School Student)</p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="active"><span class="icon">📊</span> Dashboard</a></li>
                    <li><a href="forum.php"><span class="icon">💬</span> Q&A Forum</a></li>
                    <?php if($user_type == 'student') { ?>
                    <li><a href="tutors.php"><span class="icon">👨‍🏫</span> Find Tutors</a></li>
                    <li><a href="my_sessions.php"><span class="icon">📅</span> My Sessions</a></li>
                    <?php } else { ?>
                    <li><a href="my_students.php"><span class="icon">👨‍🎓</span> My Students</a></li>
                    <li><a href="schedule.php"><span class="icon">📅</span> My Schedule</a></li>
                    <?php } ?>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn"><span class="icon">🚪</span> Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1><?php echo ($user_type == 'student' ? 'Student' : 'Tutor'); ?> Dashboard</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($user_type); ?></span>
                    <a href="notifications.php" class="notification-bell">🔔</a>
                </div>
            </header>
            
            <div class="dashboard-content">
                <div class="stats-cards">
                    <?php if($user_type == 'student') { ?>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php
                            // Count questions asked
                            $sql = "SELECT COUNT(*) as count FROM questions WHERE user_id = ?";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $row = mysqli_fetch_assoc($result);
                            echo $row['count'];
                            ?>
                        </div>
                        <div class="stat-label">Questions Asked</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php
                            // Count upcoming sessions
                            $sql = "SELECT COUNT(*) as count FROM tutoring_sessions WHERE student_id = ? AND session_date >= CURDATE() AND status != 'cancelled'";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $row = mysqli_fetch_assoc($result);
                            echo $row['count'];
                            ?>
                        </div>
                        <div class="stat-label">Upcoming Sessions</div>
                    </div>
                    <?php } else { ?>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php
                            // Count answers provided
                            $sql = "SELECT COUNT(*) as count FROM answers WHERE user_id = ?";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $row = mysqli_fetch_assoc($result);
                            echo $row['count'];
                            ?>
                        </div>
                        <div class="stat-label">Answers Provided</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php
                            // Count upcoming sessions
                            $sql = "SELECT COUNT(*) as count FROM tutoring_sessions WHERE tutor_id = ? AND session_date >= CURDATE() AND status != 'cancelled'";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $row = mysqli_fetch_assoc($result);
                            echo $row['count'];
                            ?>
                        </div>
                        <div class="stat-label">Upcoming Sessions</div>
                    </div>
                    <?php } ?>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php
                            if($user_type == 'student') {
                                // Count resolved questions
                                $sql = "SELECT COUNT(*) as count FROM questions WHERE user_id = ? AND is_resolved = 1";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                            } else {
                                // Count best answers
                                $sql = "SELECT COUNT(*) as count FROM answers WHERE user_id = ? AND is_best_answer = 1";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                            }
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $row = mysqli_fetch_assoc($result);
                            echo $row['count'];
                            ?>
                        </div>
                        <div class="stat-label"><?php echo ($user_type == 'student' ? 'Resolved Questions' : 'Best Answers'); ?></div>
                    </div>
                </div>
                
                <div class="dashboard-sections">
                    <section class="dashboard-section">
                        <div class="section-header">
                            <h2>Recent Activity</h2>
                            <a href="<?php echo ($user_type == 'student' ? 'my_questions.php' : 'my_answers.php'); ?>" class="view-all">View All</a>
                        </div>
                        <div class="activity-list">
                            <?php
                            // Get recent activity based on user type
                            if($user_type == 'student') {
                                // Recent questions
                                $sql = "SELECT id, title, created_at FROM questions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                            } else {
                                // Recent answers
                                $sql = "SELECT a.id, q.title, a.created_at FROM answers a JOIN questions q ON a.question_id = q.id WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 5";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                            }
                            
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            if(mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) {
                                    echo '<div class="activity-item">';
                                    echo '<div class="activity-content">';
                                    echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                                    echo '<p class="activity-date">' . date('M j, Y', strtotime($row['created_at'])) . '</p>';
                                    echo '</div>';
                                    echo '<a href="' . ($user_type == 'student' ? 'view_question.php?id=' . $row['id'] : 'view_answer.php?id=' . $row['id']) . '" class="activity-link">View</a>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="no-data">No recent activity found.</p>';
                            }
                            ?>
                        </div>
                    </section>
                    
                    <section class="dashboard-section">
                        <div class="section-header">
                            <h2>Upcoming Sessions</h2>
                            <a href="<?php echo ($user_type == 'student' ? 'my_sessions.php' : 'schedule.php'); ?>" class="view-all">View All</a>
                        </div>
                        <div class="sessions-list">
                            <?php
                            // Get upcoming sessions
                            if($user_type == 'student') {
                                $sql = "SELECT ts.id, ts.subject, ts.session_date, ts.start_time, ts.end_time, u.full_name AS tutor_name
                                       FROM tutoring_sessions ts 
                                       JOIN users u ON ts.tutor_id = u.id 
                                       WHERE ts.student_id = ? AND ts.session_date >= CURDATE() AND ts.status != 'cancelled'
                                       ORDER BY ts.session_date, ts.start_time LIMIT 3";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                            } else {
                                $sql = "SELECT ts.id, ts.subject, ts.session_date, ts.start_time, ts.end_time, u.full_name AS student_name
                                       FROM tutoring_sessions ts 
                                       JOIN users u ON ts.student_id = u.id 
                                       WHERE ts.tutor_id = ? AND ts.session_date >= CURDATE() AND ts.status != 'cancelled'
                                       ORDER BY ts.session_date, ts.start_time LIMIT 3";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                            }
                            
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            if(mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) {
                                    echo '<div class="session-card">';
                                    echo '<div class="session-date">' . date('j M', strtotime($row['session_date'])) . '</div>';
                                    echo '<div class="session-details">';
                                    echo '<h3>' . htmlspecialchars($row['subject']) . '</h3>';
                                    echo '<p class="session-time">' . date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])) . '</p>';
                                    echo '<p class="session-with">With: ' . htmlspecialchars($row[$user_type == 'student' ? 'tutor_name' : 'student_name']) . '</p>';
                                    echo '</div>';
                                    echo '<a href="view_session.php?id=' . $row['id'] . '" class="session-link">Details</a>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="no-data">No upcoming sessions found.</p>';
                                echo '<div class="action-prompt">';
                                echo '<p>Ready to ' . ($user_type == 'student' ? 'book a tutoring session' : 'set your availability') . '?</p>';
                                echo '<a href="' . ($user_type == 'student' ? 'tutors.php' : 'availability.php') . '" class="btn btn-primary">' . ($user_type == 'student' ? 'Find a Tutor' : 'Set Availability') . '</a>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </section>
                </div>
                
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2><?php echo ($user_type == 'student' ? 'Popular Questions' : 'Questions Needing Answers'); ?></h2>
                        <a href="forum.php" class="view-all">View All</a>
                    </div>
                    <div class="questions-grid">
                        <?php
                        // Get questions based on user type
                        if($user_type == 'student') {
                            // Popular questions that the student hasn't asked
                            $sql = "SELECT q.id, q.title, q.subject, COUNT(a.id) as answer_count, q.created_at, u.username
                                   FROM questions q 
                                   LEFT JOIN answers a ON q.id = a.question_id 
                                   JOIN users u ON q.user_id = u.id
                                   WHERE q.user_id != ?
                                   GROUP BY q.id
                                   ORDER BY answer_count DESC, q.created_at DESC
                                   LIMIT 6";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                        } else {
                            // Recent unanswered questions
                            $sql = "SELECT q.id, q.title, q.subject, q.created_at, u.username
                                   FROM questions q 
                                   JOIN users u ON q.user_id = u.id
                                   WHERE q.is_resolved = 0
                                   AND NOT EXISTS (SELECT 1 FROM answers a WHERE a.question_id = q.id AND a.user_id = ?)
                                   ORDER BY q.created_at DESC
                                   LIMIT 6";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                        }
                        
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo '<div class="question-card">';
                                echo '<div class="question-subject">' . htmlspecialchars($row['subject']) . '</div>';
                                echo '<h3 class="question-title">' . htmlspecialchars($row['title']) . '</h3>';
                                echo '<div class="question-meta">';
                                echo '<span class="question-author">By: ' . htmlspecialchars($row['username']) . '</span>';
                                echo '<span class="question-date">' . date('M j, Y', strtotime($row['created_at'])) . '</span>';
                                echo '</div>';
                                if($user_type == 'student' && isset($row['answer_count'])) {
                                    echo '<div class="answer-count">' . $row['answer_count'] . ' answers</div>';
                                }
                                echo '<a href="view_question.php?id=' . $row['id'] . '" class="question-link">View Question</a>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="no-data full-width">No questions found.</p>';
                        }
                        ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 