<?php
// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$email = "";
$email_err = $success_msg = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = clean($conn, trim($_POST["email"]));
        
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            $param_email = $email;
            
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 0) {
                    $email_err = "No account found with that email address.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // Check input errors before creating token
    if(empty($email_err)) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        
        // Set token expiry (1 hour from now)
        $expiry = date("Y-m-d H:i:s", time() + 60 * 60);
        
        // Update user with reset token
        $sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $token_hash, $expiry, $email);
            
            if(mysqli_stmt_execute($stmt)) {
                // Instead of sending an email, you can just show success message
                $success_msg = "Password reset link has been generated. Please check your email for the link.";
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
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
    <title>Reset Password - Sage</title>
    <link rel="stylesheet" href="../css/reset_password.css">
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="reset-header">
                <h1>Sage</h1>
                <p>Reset Your Password</p>
            </div>
            
            <?php 
            if(!empty($success_msg)){
                echo '<div class="alert alert-success">' . $success_msg . '</div>';
            }        
            ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Send Reset Link</button>
                </div>
                
                <p class="back-link"><a href="login.php">Back to Login</a></p>
            </form>
        </div>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>