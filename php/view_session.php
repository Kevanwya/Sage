<?php
session_start();

if(!isset($_SESSION["loggedin"])  || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
require_once "includes/email_functions.php";

if(!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: dashboard.php");
    exit;
}

$session_id = clean($conn, $_GET["id"]);
$user_id = $_SESSION["id"];
$user_type = $_SESSION["user_type"];

if($user_type == 'student') {
    $sql = "SELECT ts.*, 
                  u.full_name as tutor_name, 
                  u.email as tutor_email 
           FROM tutoring_sessions ts 
           JOIN users u ON ts.tutor_id = u.id 
           WHERE ts.id = ? AND ts.student_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $session_id, $user_id);
} else {
    $sql = "SELECT ts.*, 
                  u.full_name as student_name, 
                  u.email as student_email 
           FROM tutoring_sessions ts 
           JOIN users u ON ts.student_id = u.id 
           WHERE ts.id = ? AND ts.tutor_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $session_id, $user_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    header("location: dashboard.php");
    exit;
}

$session = mysqli_fetch_assoc($result);
$page_title = "Session Details - " . $session['subject'];

$status_message = "";
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];
    
    switch($action) {
        case 'confirm':
            $update_sql = "UPDATE tutoring_sessions SET status = 'confirmed' WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $session_id);
            if(mysqli_stmt_execute($update_stmt)) {
                $status_message = "Session has been confirmed!";
                $session['status'] = 'confirmed';
                
                $student_sql = "SELECT * FROM users WHERE id = ?";
                $student_stmt = mysqli_prepare($conn, $student_sql);
                mysqli_stmt_bind_param($student_stmt, "i", $session['student_id']);
                mysqli_stmt_execute($student_stmt);
                $student_result = mysqli_stmt_get_result($student_stmt);
                $student = mysqli_fetch_assoc($student_result);
                
                $tutor_sql = "SELECT * FROM users WHERE id = ?";
                $tutor_stmt = mysqli_prepare($conn, $tutor_sql);
                mysqli_stmt_bind_param($tutor_stmt, "i", $session['tutor_id']);
                mysqli_stmt_execute($tutor_stmt);
                $tutor_result = mysqli_stmt_get_result($tutor_stmt);
                $tutor = mysqli_fetch_assoc($tutor_result);
                
                sendSessionConfirmationEmail($session, $student, $tutor);
            }
            break;
            
        case 'complete':
            $update_sql = "UPDATE tutoring_sessions SET status = 'completed' WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $session_id);
            if(mysqli_stmt_execute($update_stmt)) {
                $status_message = "Session has been marked as completed!";
                $session['status'] = 'completed';
            }
            break;
            
        case 'cancel':
            $update_sql = "UPDATE tutoring_sessions SET status = 'cancelled' WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $session_id);
            if(mysqli_stmt_execute($update_stmt)) {
                $status_message = "Session has been cancelled!";
                $session['status'] = 'cancelled';
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Built with jdoodle.ai - View detailed information about your tutoring session including date, time, and participant information.">
    <meta property="og:title" content="<?php echo $page_title; ?> - Sage Learning Platform">
    <meta property="og:description" content="Built with jdoodle.ai - View detailed information about your tutoring session including date, time, and participant information.">
    <meta property="og:image" content="https://imagedelivery.net/FIZL8110j4px64kO6qJxWA/2fda2fd7j331cj4991ja633jdb095da46c65/public">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="<?php echo $page_title; ?> - Sage Learning Platform">
    <meta property="twitter:description" content="Built with jdoodle.ai - View detailed information about your tutoring session including date, time, and participant information.">
    <meta property="twitter:image" content="https://imagedelivery.net/FIZL8110j4px64kO6qJxWA/2fda2fd7j331cj4991ja633jdb095da46c65/public">
    <title><?php echo $page_title; ?> - Sage</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/view_session.css">
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
                    <li><a href="my_sessions.php" class="active">My Sessions</a></li>
                    <?php } else { ?>
                    <li><a href="my_students.php">My Students</a></li>
                    <li><a href="schedule.php" class="active">My Schedule</a></li>
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
                <h1>Tutoring Session Details</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <div class="session-details-container">
                <?php if(!empty($status_message)): ?>
                <div class="alert alert-success">
                    <?php echo $status_message; ?>
                </div>
                <?php endif; ?>
                
                <div class="session-header">
                    <div>
                        <h2 class="session-title"><?php echo htmlspecialchars($session['subject']); ?></h2>
                        <p class="session-meta">
                            Session ID: <?php echo $session['id']; ?> | 
                            Created: <?php echo date('M j, Y', strtotime($session['created_at'])); ?>
                        </p>
                    </div>
                    
                    <div class="session-status status-<?php echo $session['status']; ?>">
                        <?php echo ucfirst($session['status']); ?>
                    </div>
                </div>
                
                <div class="session-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <p class="info-label">Date</p>
                            <p class="info-value"><?php echo date('l, F j, Y', strtotime($session['session_date'])); ?></p>
                        </div>
                        
                        <div class="info-item">
                            <p class="info-label">Time</p>
                            <p class="info-value">
                                <?php echo date('g:i A', strtotime($session['start_time'])) . ' - ' . date('g:i A', strtotime($session['end_time'])); ?>
                            </p>
                        </div>
                        
                        <div class="info-item">
                            <p class="info-label"><?php echo ($user_type == 'student' ? 'Tutor' : 'Student'); ?></p>
                            <p class="info-value">
                                <?php echo htmlspecialchars($user_type == 'student' ? $session['tutor_name'] : $session['student_name']); ?>
                            </p>
                        </div>
                        
                        <div class="info-item">
                            <p class="info-label">Contact Email</p>
                            <p class="info-value">
                                <?php echo htmlspecialchars($user_type == 'student' ? $session['tutor_email'] : $session['student_email']); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="session-description">
                    <h3 class="description-title">Session Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($session['description'])); ?></p>
                </div>
                
                <?php if($session['status'] != 'cancelled' && $session['status'] != 'completed'): ?>
                <div class="session-actions">
                    <?php if($user_type == 'tutor' && $session['status'] == 'pending'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="confirm">
                        <button type="submit" class="btn btn-primary">Confirm Session</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if(($user_type == 'tutor' && $session['status'] == 'confirmed') || 
                             ($user_type == 'student' && $session['status'] == 'confirmed' && 
                              strtotime($session['session_date'] . ' ' . $session['end_time']) < time())): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="complete">
                        <button type="submit" class="btn btn-primary">Mark as Completed</button>
                    </form>
                    <?php endif; ?>
                    
                    <form method="post" onsubmit="return confirm('Are you sure you want to cancel this session?');">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn btn-secondary">Cancel Session</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <a href="<?php echo $user_type == 'student' ? 'my_sessions.php' : 'schedule.php'; ?>" class="btn btn-secondary">
                    Back to <?php echo $user_type == 'student' ? 'My Sessions' : 'Schedule'; ?>
                </a>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 