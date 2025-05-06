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

// Define variables and initialize with empty values
$current_password = $new_password = $confirm_password = $email = "";
$current_password_err = $new_password_err = $confirm_password_err = $email_err = "";
$success_msg = $error_msg = "";

// Get user information
$user_id = $_SESSION["id"];
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Processing profile update when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    
    // Validate email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        // Check if email is valid
        if(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // If email is different than current, check if it's already in use
            if(trim($_POST["email"]) !== $user["email"]) {
                $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
                
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "si", $param_email, $user_id);
                    
                    $param_email = trim($_POST["email"]);
                    
                    if(mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_store_result($stmt);
                        
                        if(mysqli_stmt_num_rows($stmt) == 1) {
                            $email_err = "This email is already registered.";
                        } else {
                            $email = clean($conn, trim($_POST["email"]));
                        }
                    } else {
                        $error_msg = "Oops! Something went wrong. Please try again later.";
                    }
                    
                    mysqli_stmt_close($stmt);
                }
            } else {
                $email = $user["email"]; // Email not changed
            }
        }
    }
    
    // Check input errors before updating profile
    if(empty($email_err)) {
        
        // Prepare an update statement
        $sql = "UPDATE users SET email = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_email, $param_id);
            
            // Set parameters
            $param_email = $email;
            $param_id = $user_id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                $success_msg = "Profile updated successfully!";
                
                // Update session variable if email changed
                if($email !== $user["email"]) {
                    $user["email"] = $email;
                }
            } else {
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Processing password change when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    
    // Validate current password
    if(empty(trim($_POST["current_password"]))) {
        $current_password_err = "Please enter your current password.";     
    } else {
        $current_password = trim($_POST["current_password"]);
        
        // Verify current password
        if(!password_verify($current_password, $user["password"])) {
            $current_password_err = "Current password is incorrect.";
        }
    }
    
    // Validate new password
    if(empty(trim($_POST["new_password"]))) {
        $new_password_err = "Please enter a new password.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6) {
        $new_password_err = "Password must have at least 6 characters.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm the password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before updating the password
    if(empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        
        // Prepare an update statement
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_password, $param_id);
            
            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $param_id = $user_id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                $success_msg = "Password changed successfully!";
            } else {
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="../css/profile.css">
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
                    <li><a href="my_sessions.php">Sessions</a></li>
                    <?php } else { ?>
                    <li><a href="my_students.php">Students</a></li>
                    <li><a href="schedule.php">Schedule</a></li>
                    <?php } ?>
                    <li><a href="profile.php" class="active">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>My Profile</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <div class="profile-container">
                <?php 
                if(!empty($success_msg)) {
                    echo '<div class="alert alert-success">' . $success_msg . '</div>';
                }
                if(!empty($error_msg)) {
                    echo '<div class="alert alert-danger">' . $error_msg . '</div>';
                }
                ?>
                
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <p class="profile-meta">
                            <span class="user-type-badge"><?php echo ucfirst($user['user_type']); ?></span>
                            <span class="join-date">Joined: <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                        </p>
                    </div>
                </div>
                
                <div class="profile-sections">
                    <section class="profile-section">
                        <h3>Profile Information</h3>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="profile-form">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>
                                <span class="form-help">Full Name cannot be changed</span>
                            </div>
                            
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <span class="form-help">Usernames cannot be changed</span>
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($user['email']); ?>">
                                <span class="invalid-feedback"><?php echo $email_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label>User Type</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['user_type']); ?>" disabled>
                                <span class="form-help">Account Type cannot be changed</span>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </section>
                    
                    <section class="profile-section">
                        <h3>Change Password</h3>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="password-form">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $current_password_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                                <span class="form-help">Must be at least 6 characters long</span>
                            </div>
                            
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 