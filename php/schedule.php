<?php
session_start();

if(!isset($_SESSION["loggedin"])  || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

if($_SESSION["user_type"] !== "tutor") {
    header("location: dashboard.php");
    exit;
}

require_once "config.php";

$user_id = $_SESSION["id"];
$active_tab = isset($_GET['tab']) ? clean($conn, $_GET['tab']) : 'upcoming';

$sql = "SELECT ts.*, u.full_name as student_name, u.email as student_email 
        FROM tutoring_sessions ts 
        JOIN users u ON ts.student_id = u.id 
        WHERE ts.tutor_id = ? ";

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

$availability_sql = "SELECT * FROM tutor_availability WHERE tutor_id = ? ORDER BY 
                    CASE day_of_week 
                        WHEN 'Monday' THEN 1 
                        WHEN 'Tuesday' THEN 2 
                        WHEN 'Wednesday' THEN 3 
                        WHEN 'Thursday' THEN 4 
                        WHEN 'Friday' THEN 5 
                        WHEN 'Saturday' THEN 6 
                        WHEN 'Sunday' THEN 7 
                    END, start_time";
$availability_stmt = mysqli_prepare($conn, $availability_sql);
mysqli_stmt_bind_param($availability_stmt, "i", $user_id);
mysqli_stmt_execute($availability_stmt);
$availability_result = mysqli_stmt_get_result($availability_stmt);

$availability = [];
while($row = mysqli_fetch_assoc($availability_result)) {
    $availability[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Sage</title>
    <link rel="stylesheet" href="../css/schedule.css">
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
                    <li><a href="my_students.php">My Students</a></li>
                    <li><a href="schedule.php" class="active">My Schedule</a></li>
                    <li><a href="availability.php">Set Availability</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>My Schedule</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                    <a href="availability.php" class="btn btn-primary">Set Availability</a>
                </div>
            </header>
            
            <div class="schedule-container">
                <div class="tabs">
                    <a href="?tab=upcoming" class="tab <?php echo ($active_tab == 'upcoming') ? 'active' : ''; ?>">Upcoming Sessions</a>
                    <a href="?tab=past" class="tab <?php echo ($active_tab == 'past') ? 'active' : ''; ?>">Past Sessions</a>
                    <a href="?tab=cancelled" class="tab <?php echo ($active_tab == 'cancelled') ? 'active' : ''; ?>">Cancelled Sessions</a>
                </div>
                
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
                                    <p class="session-student">with <?php echo htmlspecialchars($session['student_name']); ?></p>
                                    <p class="student-email"><?php echo htmlspecialchars($session['student_email']); ?></p>
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
                        <p>No <?php echo $active_tab; ?> sessions found.</p>
                        <?php if(count($availability) == 0 && $active_tab == 'upcoming'): ?>
                            <a href="availability.php" class="btn btn-primary">Set Your Availability</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="availability-summary">
                <h2>Your Current Availability</h2>
                
                <?php if(count($availability) > 0): ?>
                    <div class="availability-grid">
                        <?php
                        $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
                        foreach($days as $day) {
                            echo '<div class="day-column">';
                            echo '<h3 class="day-header">' . $day . '</h3>';
                            
                            $day_slots = array_filter($availability, function($slot) use ($day) {
                                return $slot['day_of_week'] === $day;
                            });
                            
                            if(count($day_slots) > 0) {
                                echo '<div class="time-slots">';
                                foreach($day_slots as $slot) {
                                    echo '<div class="time-slot">';
                                    echo date('g:i A', strtotime($slot['start_time'])) . ' - ' . date('g:i A', strtotime($slot['end_time']));
                                    echo '</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '<div class="no-slots">Not Available</div>';
                            }
                            
                            echo '</div>';
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <div class="no-availability">
                        <p>You haven't set any availability yet. Students can only book sessions during your available times.</p>
                        <a href="availability.php" class="btn btn-primary">Set Your Availability</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 