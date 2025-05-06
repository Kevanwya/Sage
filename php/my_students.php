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

// Get all students who have booked sessions with this tutor
$user_id = $_SESSION["id"];

$sql = "SELECT DISTINCT u.id, u.username, u.full_name, u.email, 
               COUNT(ts.id) as session_count,
               MAX(ts.session_date) as latest_session_date
        FROM users u
        JOIN tutoring_sessions ts ON u.id = ts.student_id
        WHERE ts.tutor_id = ?
        GROUP BY u.id
        ORDER BY latest_session_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$students = [];
while($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students</title>
    <link rel="stylesheet" href="../css/my_students.css">
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
                <h1>My Students</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <div class="students-container">
                <div class="students-header">
                    <h2>Students You've Tutored</h2>
                    <div class="student-count"><?php echo count($students); ?> Student<?php echo count($students) != 1 ? 's' : ''; ?></div>
                </div>
                
                <?php if(count($students) > 0): ?>
                    <div class="students-grid">
                        <?php foreach($students as $student): ?>
                            <div class="student-card">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                </div>
                                <div class="student-info">
                                    <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
                                    <p class="student-username">@<?php echo htmlspecialchars($student['username']); ?></p>
                                    <p class="student-email"><?php echo htmlspecialchars($student['email']); ?></p>
                                    <p class="student-stats">
                                        <span><?php echo $student['session_count']; ?> Session<?php echo $student['session_count'] != 1 ? 's' : ''; ?></span>
                                        <span>Latest: <?php echo date('M j, Y', strtotime($student['latest_session_date'])); ?></span>
                                    </p>
                                </div>
                                <div class="student-actions">
                                    <a href="student_sessions.php?student_id=<?php echo $student['id']; ?>" class="btn btn-primary">View Sessions</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-students">
                        <p>You haven't tutored any students yet.</p>
                        <p>Once students book sessions with you, they will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 