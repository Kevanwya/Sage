<?php
require_once  "config.php";

$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = $token_err = "";

if(!isset($_GET["token"]) || empty(trim($_GET["token"]))) {
    $token_err = "Invalid password reset link.";
} else {
    $token = $_GET["token"];
    $token_hash = hash('sha256', $token);
    
    $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token_hash);
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 0) {
                $token_err = "Invalid or expired password reset link.";
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST" && empty($token_err)) {
    
    if(empty(trim($_POST["new_password"]))) {
        $new_password_err = "Please enter the new password.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6) {
        $new_password_err = "Password must have at least 6 characters.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm the password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    if(empty($new_password_err) && empty($confirm_password_err)) {
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $param_password, $token_hash);
            
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            if(mysqli_stmt_execute($stmt)) {
                header("location: login.php");
                exit();
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
    <meta name="description" content="Built with jdoodle.ai - Create a new password for your Sage account. Reset your password securely.">
    <meta property="og:title" content="New Password - Sage Learning Platform">
    <meta property="og:description" content="Built with jdoodle.ai - Create a new password for your Sage account. Reset your password securely.">
    <meta property="og:image" content="https://imagedelivery.net/FIZL8110j4px64kO6qJxWA/2fda2fd7j331cj4991ja633jdb095da46c65/public">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="New Password - Sage Learning Platform">
    <meta property="twitter:description" content="Built with jdoodle.ai - Create a new password for your Sage account. Reset your password securely.">
    <meta property="twitter:image" content="https://imagedelivery.net/FIZL8110j4px64kO6qJxWA/2fda2fd7j331cj4991ja633jdb095da46c65/public">
    <title>New Password - Sage</title>
    <link rel="stylesheet" href="../css/new_password.css">
</head>
<body>
    <div class="container">
        <div class="password-container">
            <div class="password-header">
                <h1>Sage</h1>
                <p>Create New Password</p>
            </div>
            
            <?php 
            if(!empty($token_err)){
                echo '<div class="alert alert-danger">' . $token_err . '</div>';
                echo '<p class="back-link"><a href="reset_password.php">Request a new password reset link</a></p>';
            } else {        
            ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?token=" . $token); ?>" method="post">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
                
                <p class="back-link"><a href="login.php">Back to Login</a></p>
            </form>
            
            <?php } ?>
        </div>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 