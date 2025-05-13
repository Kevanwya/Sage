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

// Define variables
$tutors = [];
$search = "";

// Process search
if($_SERVER["REQUEST_METHOD"] == "GET") {
    if(isset($_GET['search']) && !empty($_GET['search'])) {
        $search = clean($conn, $_GET['search']);
    }
}

// Base query
$sql = "SELECT u.id, u.username, u.full_name, COUNT(a.id) as answer_count 
        FROM users u 
        LEFT JOIN answers a ON u.id = a.user_id 
        WHERE u.user_type = 'tutor'";

// Add search condition
$params = [];
$types = "";

if(!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// Group by and order
$sql .= " GROUP BY u.id ORDER BY answer_count DESC, u.full_name ASC";

$stmt = mysqli_prepare($conn, $sql);

if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while($row = mysqli_fetch_assoc($result)) {
    $tutors[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutors</title>
    <link rel="stylesheet" href="../css/tutors.css">
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
                    <li><a href="tutors.php" class="active">Find Tutors</a></li>
                    <li><a href="my_sessions.php">Sessions</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Find Tutors</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <div class="tutors-container">
                <div class="search-filter-section">
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="search-form">
                        <div class="search-input">
                            <input type="text" name="search" placeholder="Search tutors..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <?php if(!empty($search)): ?>
                            <div class="filter-section">
                                <a href="tutors.php" class="btn btn-clear">Clear</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="tutors-grid">
                    <?php if(count($tutors) > 0): ?>
                        <?php foreach($tutors as $tutor): ?>
                            <div class="tutor-card">
                                <div class="tutor-avatar">
                                    <?php echo strtoupper(substr($tutor['full_name'], 0, 1)); ?>
                                </div>
                                <div class="tutor-info">
                                    <h3><?php echo htmlspecialchars($tutor['full_name']); ?></h3>
                                    <p class="tutor-username">@<?php echo htmlspecialchars($tutor['username']); ?></p>
                                    <p class="tutor-stats">
                                        <span><?php echo $tutor['answer_count']; ?> Answers</span>
                                    </p>
                                </div>
                                <a href="schedule_session.php?tutor_id=<?php echo $tutor['id']; ?>" class="btn btn-primary">Book Session</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <p>No tutors found matching your criteria.</p>
                            <a href="tutors.php" class="btn btn-primary">View All Tutors</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>

 