<?php
//  Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if user is a tutor
if($_SESSION["user_type"] !== "tutor") {
    header("location: dashboard.php");
    exit;
}

// Include config file
require_once "config.php";

// Check if student_id is provided
if(!isset($_GET["student_id"]) || empty($_GET["student_id"])) {
    header("location: my_students.php");
    exit;
}

$student_id = clean($conn, $_GET["student_id"]);
$tutor_id = $_SESSION["id"];

// Get student details
$student_sql = "SELECT * FROM users WHERE id = ? AND user_type = 'student'";
$student_stmt = mysqli_prepare($conn, $student_sql);
mysqli_stmt_bind_param($student_stmt, "i", $student_id);
mysqli_stmt_execute($student_stmt);
$student_result = mysqli_stmt_get_result($student_stmt);

if(mysqli_num_rows($student_result) == 0) {
    header("location: my_students.php");
    exit;
}

$student = mysqli_fetch_assoc($student_result);

// Get all sessions with this student
$sessions_sql = "SELECT * FROM tutoring_sessions 
                WHERE tutor_id = ? AND student_id = ? 
                ORDER BY session_date DESC, start_time DESC";
$sessions_stmt = mysqli_prepare($conn, $sessions_sql);
mysqli_stmt_bind_param($sessions_stmt, "ii", $tutor_id, $student_id);
mysqli_stmt_execute($sessions_stmt);
$sessions_result = mysqli_stmt_get_result($sessions_stmt);

$sessions = [];
while($row = mysqli_fetch_assoc($sessions_result)) {
    $sessions[] = $row;
}

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
                COUNT(CASE WHEN status = 'pending' OR status = 'confirmed' THEN 1 END) as upcoming_sessions,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_sessions,
                MIN(session_date) as first_session_date
              FROM tutoring_sessions 
              WHERE tutor_id = ? AND student_id = ?";
$stats_stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stats_stmt, "ii", $tutor_id, $student_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions with <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link rel="stylesheet" href="../css/student_sessions.css">
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
                    <li><a href="my_students.php" class="active">Students</a></li>
                    <li><a href="schedule.php">Schedule</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Sessions with <?php echo htmlspecialchars($student['full_name']); ?></h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <div class="student-profile">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                </div>
                <div class="student-info">
                    <h2 class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></h2>
                    <p class="student-username">@<?php echo htmlspecialchars($student['username']); ?></p>
                    <p class="student-email"><?php echo htmlspecialchars($student['email']); ?></p>
                    <p class="student-since">Student since: <?php echo date('F Y', strtotime($student['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="student-stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_sessions']; ?></div>
                    <div class="stat-label">Total Sessions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['completed_sessions']; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['upcoming_sessions']; ?></div>
                    <div class="stat-label">Upcoming</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['cancelled_sessions']; ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['first_session_date'] ? date('M Y', strtotime($stats['first_session_date'])) : 'N/A'; ?></div>
                    <div class="stat-label">First Session</div>
                </div>
            </div>
            
            <div class="sessions-container">
                <h2>Session History</h2>
                
                <?php if(count($sessions) > 0): ?>
                    <div class="sessions-list">
                        <?php foreach($sessions as $session): ?>
                            <div class="session-card status-<?php echo $session['status']; ?>">
                                <div class="session-date-wrapper">
                                    <div class="session-date">
                                        <span class="date-day"><?php echo date('d', strtotime($session['session_date'])); ?></span>
                                        <span class="date-month"><?php echo date('M', strtotime($session['session_date'])); ?></span>
                                    </div>
                                    <div class="session-time">
                                        <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                                    </div>
                                </div>
                                
                                <div class="session-info">
                                    <h3 class="session-subject"><?php echo htmlspecialchars($session['subject']); ?></h3>
                                    <p class="session-description"><?php echo htmlspecialchars(substr($session['description'], 0, 100)); ?><?php echo strlen($session['description']) > 100 ? '...' : ''; ?></p>
                                    <p class="session-status">Status: <span class="status-badge badge-<?php echo $session['status']; ?>"><?php echo ucfirst($session['status']); ?></span></p>
                                </div>
                                
                                <div class="session-actions">
                                    <a href="view_session.php?id=<?php echo $session['id']; ?>" class="btn btn-primary">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-sessions">
                        <p>No session records found with this student.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="back-link">
                <a href="my_students.php" class="btn btn-back">Back to Students List</a>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 