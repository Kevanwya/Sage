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
require_once "includes/email_functions.php";

// Check if tutor_id is provided
if(!isset($_GET['tutor_id']) || empty($_GET['tutor_id'])) {
    header("location: tutors.php");
    exit;
}

$tutor_id = clean($conn, $_GET['tutor_id']);

// Get tutor details
$tutor_sql = "SELECT * FROM users WHERE id = ? AND user_type = 'tutor'";
$tutor_stmt = mysqli_prepare($conn, $tutor_sql);
mysqli_stmt_bind_param($tutor_stmt, "i", $tutor_id);
mysqli_stmt_execute($tutor_stmt);
$tutor_result = mysqli_stmt_get_result($tutor_stmt);

if(mysqli_num_rows($tutor_result) == 0) {
    header("location: tutors.php");
    exit;
}

$tutor = mysqli_fetch_assoc($tutor_result);

// Define variables and initialize with empty values
$subject = $date = $start_time = $end_time = $description = "";
$subject_err = $date_err = $start_time_err = $end_time_err = $description_err = "";
$success_msg = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate subject
    if(empty(trim($_POST["subject"]))) {
        $subject_err = "Please enter a subject for the session.";     
    } else {
        $subject = clean($conn, trim($_POST["subject"]));
    }
    
    // Validate date
    if(empty(trim($_POST["date"]))) {
        $date_err = "Please select a date for the session.";     
    } else {
        $date = clean($conn, trim($_POST["date"]));
        // Check if date is not in the past
        if(strtotime($date) < strtotime(date('Y-m-d'))) {
            $date_err = "Session date cannot be in the past.";
        }
    }
    
    // Validate start time
    if(empty(trim($_POST["start_time"]))) {
        $start_time_err = "Please select a start time.";     
    } else {
        $start_time = clean($conn, trim($_POST["start_time"]));
    }
    
    // Validate end time
    if(empty(trim($_POST["end_time"]))) {
        $end_time_err = "Please select an end time.";     
    } else {
        $end_time = clean($conn, trim($_POST["end_time"]));
        // Check if end time is after start time
        if(!empty($start_time) && strtotime($end_time) <= strtotime($start_time)) {
            $end_time_err = "End time must be after start time.";
        }
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))) {
        $description_err = "Please provide a description for the session.";     
    } else {
        $description = clean($conn, trim($_POST["description"]));
    }
    
    // Check input errors before inserting in database
    if(empty($subject_err) && empty($date_err) && empty($start_time_err) && empty($end_time_err) && empty($description_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO tutoring_sessions (tutor_id, student_id, subject, description, session_date, start_time, end_time) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "iisssss", $param_tutor_id, $param_student_id, $param_subject, $param_description, $param_date, $param_start_time, $param_end_time);
            
            // Set parameters
            $param_tutor_id = $tutor_id;
            $param_student_id = $_SESSION["id"];
            $param_subject = $subject;
            $param_description = $description;
            $param_date = $date;
            $param_start_time = $start_time;
            $param_end_time = $end_time;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                $session_id = mysqli_insert_id($conn);
                
                // Set success message
                $success_msg = "Your session request has been sent! Redirecting to session details...";
                
                // Redirect after 2 seconds
                header("refresh:2;url=view_session.php?id=" . $session_id);
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Get popular subjects for autocomplete
$subject_sql = "SELECT subject, COUNT(*) as count FROM questions GROUP BY subject ORDER BY count DESC LIMIT 10";
$subject_result = mysqli_query($conn, $subject_sql);
$popular_subjects = [];

while($row = mysqli_fetch_assoc($subject_result)) {
    $popular_subjects[] = $row['subject'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Session - Sage</title>
    <link rel="stylesheet" href="../css/schedule_session.css">
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
                    <li><a href="my_sessions.php">My Sessions</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Schedule Tutoring Session</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <div class="session-container">
                <?php 
                if(!empty($success_msg)){
                    echo '<div class="alert alert-success">' . $success_msg . '</div>';
                }
                ?>
                
                <div class="tutor-info">
                    <div class="tutor-avatar">
                        <?php echo strtoupper(substr($tutor['full_name'], 0, 1)); ?>
                    </div>
                    <div class="tutor-details">
                        <h2><?php echo htmlspecialchars($tutor['full_name']); ?></h2>
                        <p class="tutor-username">@<?php echo htmlspecialchars($tutor['username']); ?></p>
                    </div>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?tutor_id=" . $tutor_id); ?>" class="session-form">
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control <?php echo (!empty($subject_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $subject; ?>" list="subjects">
                        <datalist id="subjects">
                            <?php
                            foreach($popular_subjects as $subj) {
                                echo '<option value="' . htmlspecialchars($subj) . '">';
                            }
                            ?>
                        </datalist>
                        <span class="invalid-feedback"><?php echo $subject_err; ?></span>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control <?php echo (!empty($date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $date; ?>" min="<?php echo date('Y-m-d'); ?>">
                            <span class="invalid-feedback"><?php echo $date_err; ?></span>
                        </div>
                        
                        <div class="form-group half">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control <?php echo (!empty($start_time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $start_time; ?>">
                            <span class="invalid-feedback"><?php echo $start_time_err; ?></span>
                        </div>
                        
                        <div class="form-group half">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control <?php echo (!empty($end_time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $end_time; ?>">
                            <span class="invalid-feedback"><?php echo $end_time_err; ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Session Description</label>
                        <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="5" placeholder="Describe what you need help with and any specific topics you want to cover"><?php echo $description; ?></textarea>
                        <span class="invalid-feedback"><?php echo $description_err; ?></span>
                    </div>
                    
                    <div class="form-buttons">
                        <a href="tutors.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Request Session</button>
                    </div>
                </form>
                
                <div class="session-tips">
                    <h3>Tips for a productive session</h3>
                    <ul>
                        <li><strong>Be specific</strong> about what you want to learn or practice.</li>
                        <li><strong>Share your level</strong> of understanding on the subject.</li>
                        <li><strong>Prepare questions</strong> in advance to make the most of your time.</li>
                        <li><strong>Plan ahead</strong> - book sessions at least 24 hours in advance when possible.</li>
                        <li><strong>Be on time</strong> for your session to maximize your learning opportunity.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 