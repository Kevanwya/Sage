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

// Define variables and initialize with empty values
$day = $start_time = $end_time = "";
$day_err = $start_time_err = $end_time_err = "";
$success_msg = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_availability"])) {
    
    // Validate day
    if(empty(trim($_POST["day"]))) {
        $day_err = "Please select a day.";
    } else {
        $day = clean($conn, trim($_POST["day"]));
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
    
    // Check input errors before inserting in database
    if(empty($day_err) && empty($start_time_err) && empty($end_time_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO tutor_availability (tutor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "isss", $param_tutor_id, $param_day, $param_start_time, $param_end_time);
            
            // Set parameters
            $param_tutor_id = $_SESSION["id"];
            $param_day = $day;
            $param_start_time = $start_time;
            $param_end_time = $end_time;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                $success_msg = "Availability added successfully!";
                $day = $start_time = $end_time = ""; // Clear form
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Process delete availability
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_availability"])) {
    $availability_id = clean($conn, $_POST["availability_id"]);
    
    // Make sure the availability belongs to this tutor
    $sql = "DELETE FROM tutor_availability WHERE id = ? AND tutor_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $availability_id, $_SESSION["id"]);
    
    if(mysqli_stmt_execute($stmt)) {
        $success_msg = "Availability slot removed successfully!";
    }
}

// Get current availability
$user_id = $_SESSION["id"];
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
    <title>My Availability - Sage</title>
    <link rel="stylesheet" href="../css/availability.css">
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
                    <li><a href="schedule.php">My Schedule</a></li>
                    <li><a href="availability.php" class="active">Set Availability</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Set Your Availability</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            
            <div class="availability-container">
                <div class="card add-availability">
                    <h2 class="card-title">Add Availability Slot</h2>
                    <form method="post" class="availability-form">
                        <div class="form-group">
                            <label>Day of Week</label>
                            <select name="day" class="form-control <?php echo (!empty($day_err)) ? 'is-invalid' : ''; ?>">
                                <option value="">Select Day</option>
                                <option value="Monday" <?php echo ($day == "Monday") ? "selected" : ""; ?>>Monday</option>
                                <option value="Tuesday" <?php echo ($day == "Tuesday") ? "selected" : ""; ?>>Tuesday</option>
                                <option value="Wednesday" <?php echo ($day == "Wednesday") ? "selected" : ""; ?>>Wednesday</option>
                                <option value="Thursday" <?php echo ($day == "Thursday") ? "selected" : ""; ?>>Thursday</option>
                                <option value="Friday" <?php echo ($day == "Friday") ? "selected" : ""; ?>>Friday</option>
                                <option value="Saturday" <?php echo ($day == "Saturday") ? "selected" : ""; ?>>Saturday</option>
                                <option value="Sunday" <?php echo ($day == "Sunday") ? "selected" : ""; ?>>Sunday</option>
                            </select>
                            <span class="invalid-feedback"><?php echo $day_err; ?></span>
                        </div>
                        
                        <div class="form-row">
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
                        
                        <button type="submit" name="add_availability" class="btn btn-primary">Add Availability</button>
                    </form>
                </div>
                
                <div class="card current-availability">
                    <h2 class="card-title">Current Availability</h2>
                    
                    <?php if(count($availability) > 0): ?>
                        <div class="availability-list">
                            <?php foreach($availability as $slot): ?>
                                <div class="availability-slot">
                                    <div class="slot-info">
                                        <div class="slot-day"><?php echo htmlspecialchars($slot['day_of_week']); ?></div>
                                        <div class="slot-time">
                                            <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
                                        </div>
                                    </div>
                                    <form method="post" class="slot-actions">
                                        <input type="hidden" name="availability_id" value="<?php echo $slot['id']; ?>">
                                        <button type="submit" name="delete_availability" class="btn btn-danger">Remove</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-availability">
                            <p>You haven't set any availability slots yet.</p>
                            <p>Add your available times above to let students know when you can tutor.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="availability-tips">
                    <h3>Tips for Setting Availability</h3>
                    <ul>
                        <li>Set regular, consistent hours that students can depend on.</li>
                        <li>Consider adding a mix of morning, afternoon, and evening slots to accommodate different student schedules.</li>
                        <li>Block out time for breaks and personal commitments.</li>
                        <li>Update your availability if your schedule changes.</li>
                        <li>Students will only be able to book sessions during your available times.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 