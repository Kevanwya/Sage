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
require_once "includes/email_functions.php";

// Check if session ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: dashboard.php");
    exit;
}

$session_id = clean($conn, $_GET["id"]);
$user_id = $_SESSION["id"];
$user_type = $_SESSION["user_type"];

// Get session details based on user type
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

// Check if session exists and belongs to user
if(mysqli_num_rows($result) == 0) {
    header("location: dashboard.php");
    exit;
}

$session = mysqli_fetch_assoc($result);
$page_title = "Session Details - " . $session['subject'];

// Handle session status updates
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
                
                // Get student details for email
                $student_sql = "SELECT * FROM users WHERE id = ?";
                $student_stmt = mysqli_prepare($conn, $student_sql);
                mysqli_stmt_bind_param($student_stmt, "i", $session['student_id']);
                mysqli_stmt_execute($student_stmt);
                $student_result = mysqli_stmt_get_result($student_stmt);
                $student = mysqli_fetch_assoc($student_result);
                
                // Get tutor details for email
                $tutor_sql = "SELECT * FROM users WHERE id = ?";
                $tutor_stmt = mysqli_prepare($conn, $tutor_sql);
                mysqli_stmt_bind_param($tutor_stmt, "i", $session['tutor_id']);
                mysqli_stmt_execute($tutor_stmt);
                $tutor_result = mysqli_stmt_get_result($tutor_stmt);
                $tutor = mysqli_fetch_assoc($tutor_result);
                
                // Send confirmation email
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
    <title><?php echo $page_title; ?> - Sage</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .session-details-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .session-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .status-confirmed {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .status-completed {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        
        .status-cancelled {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .session-title {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .session-meta {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .session-info {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .info-value {
            color: var(--gray);
        }
        
        .session-description {
            margin-bottom: 30px;
        }
        
        .description-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .session-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
            border: 1px solid #86efac;
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
 