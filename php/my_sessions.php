<?php
//  Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if user is a student
if($_SESSION["user_type"] !== "student") {
    header("location: dashboard.php");
    exit;
}

// Include config file
require_once "config.php";

// Set up variables
$user_id = $_SESSION["id"];
$active_tab = isset($_GET['tab']) ? clean($conn, $_GET['tab']) : 'upcoming';

// Get sessions based on active tab
$sql = "SELECT ts.*, u.full_name as tutor_name 
        FROM tutoring_sessions ts 
        JOIN users u ON ts.tutor_id = u.id 
        WHERE ts.student_id = ? ";

if($active_tab == 'upcoming') {
    $sql .= "AND ts.session_date >= CURDATE() AND ts.status != 'cancelled' ";
} elseif($active_tab == 'past') {
    $sql .= "AND (ts.session_date < CURDATE() OR ts.status = 'completed') ";
} elseif($active_tab == 'cancelled') {
    $sql .= "AND ts.status = 'cancelled' ";
}

$sql .= "ORDER BY ts.session_date, ts.start_time";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$sessions = [];
while($row = mysqli_fetch_assoc($result)) {
    $sessions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions</title>
    <link rel="stylesheet" href="../css/my_sessions.css">
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
                    <li><a href="tutors.php">Find Tutors</a></li>
                    <li><a href="my_sessions.php" class="active">Sessions</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Tutoring Sessions</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <div class="sessions-container">
                <div class="tabs">
                    <a href="?tab=upcoming" class="tab <?php echo ($active_tab == 'upcoming') ? 'active' : ''; ?>">Upcoming</a>
                    <a href="?tab=past" class="tab <?php echo ($active_tab == 'past') ? 'active' : ''; ?>">Past</a>
                    <a href="?tab=cancelled" class="tab <?php echo ($active_tab == 'cancelled') ? 'active' : ''; ?>">Cancelled</a>
                </div>
                
                <div class="sessions-list">
                    <?php if(count($sessions) > 0): ?>
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
                                    <p class="session-tutor">with <?php echo htmlspecialchars($session['tutor_name']); ?></p>
                                    <p class="session-status">Status: <span class="status-badge badge-<?php echo $session['status']; ?>"><?php echo ucfirst($session['status']); ?></span></p>
                                </div>
                                
                                <div class="session-actions">
                                    <a href="view_session.php?id=<?php echo $session['id']; ?>" class="btn btn-primary">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-sessions">
                            <p>No <?php echo $active_tab; ?> sessions found.</p>
                            <?php if($active_tab == 'upcoming'): ?>
                                <a href="tutors.php" class="btn btn-primary">Book a Session</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 