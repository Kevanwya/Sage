<?php
//  Include config file and email functions
require_once "config.php";
require_once "includes/email_functions.php";
 

// Define variables and initialize with empty values
$username = $password = $confirm_password = $email = $full_name = $user_type = "";
$username_err = $password_err = $confirm_password_err = $email_err = $full_name_err = $user_type_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate username
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = clean($conn, trim($_POST["username"]));
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        // Check if email is valid
        if(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Check if email already exists
            $sql = "SELECT id FROM users WHERE email = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $param_email);
                
                $param_email = trim($_POST["email"]);
                
                if(mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) == 1) {
                        $email_err = "This email is already registered.";
                    } else {
                        $email = clean($conn, trim($_POST["email"]));
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Validate full name
    if(empty(trim($_POST["full_name"]))) {
        $full_name_err = "Please enter your full name.";     
    } else {
        $full_name = clean($conn, trim($_POST["full_name"]));
    }
    
    // Validate user type
    if(empty(trim($_POST["user_type"]))) {
        $user_type_err = "Please select a user type.";
    } else {
        $user_type = clean($conn, trim($_POST["user_type"]));
        if($user_type != "student" && $user_type != "tutor") {
            $user_type_err = "Invalid user type selection.";
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err) && empty($full_name_err) && empty($user_type_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, email, user_type, full_name) VALUES (?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssss", $param_username, $param_password, $param_email, $param_user_type, $param_full_name);
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_email = $email;
            $param_user_type = $user_type;
            $param_full_name = $full_name;
            
                       // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                // Send registration confirmation email
                sendRegistrationEmail($email, $username, $full_name);
                
                // Redirect to login page
                header("location: login.php");
            } else {
                echo "Oops! Something went wrong. Please try again later.";
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
    <title>Register - Sage</title>
    <link rel="stylesheet" href="../css/register.css">
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h1>Sage</h1>
                <p>Create an Account</p>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $full_name; ?>">
                    <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>User Type</label>
                    <div class="user-type-selection">
                        <label class="radio-label">
                            <input type="radio" name="user_type" value="student" <?php echo ($user_type == "student") ? "checked" : ""; ?>>
                            <span class="radio-custom"></span>
                            Student
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="user_type" value="tutor" <?php echo ($user_type == "tutor") ? "checked" : ""; ?>>
                            <span class="radio-custom"></span>
                            Tutor
                        </label>
                    </div>
                    <span class="invalid-feedback"><?php echo $user_type_err; ?></span>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    
                    <div class="form-group half">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
                
                <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
            </form>
        </div>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 