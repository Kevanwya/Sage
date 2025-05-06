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
$title = $subject = $content = "";
$title_err = $subject_err = $content_err = "";
$success_msg = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate title
    if(empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title for your question.";     
    } elseif(strlen(trim($_POST["title"])) > 255) {
        $title_err = "Title cannot exceed 255 characters.";
    } else {
        $title = clean($conn, trim($_POST["title"]));
    }
    
    // Validate subject
    if(empty(trim($_POST["subject"]))) {
        $subject_err = "Please enter a subject.";     
    } elseif(strlen(trim($_POST["subject"])) > 100) {
        $subject_err = "Subject cannot exceed 100 characters.";
    } else {
        $subject = clean($conn, trim($_POST["subject"]));
    }
    
    // Validate content
    if(empty(trim($_POST["content"]))) {
        $content_err = "Please enter your question details.";     
    } else {
        $content = clean($conn, trim($_POST["content"]));
    }
    
    // Check input errors before inserting in database
    if(empty($title_err) && empty($subject_err) && empty($content_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO questions (user_id, title, subject, content) VALUES (?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "isss", $param_user_id, $param_title, $param_subject, $param_content);
            
            // Set parameters
            $param_user_id = $_SESSION["id"];
            $param_title = $title;
            $param_subject = $subject;
            $param_content = $content;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                $question_id = mysqli_insert_id($conn);
                
                // Check if there are file uploads
                if(!empty($_FILES['attachments']['name'][0])) {
                    $upload_dir = "../uploads/";
                    
                    // Create directory if it doesn't exist
                    if(!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Process each uploaded file
                    $file_count = count($_FILES['attachments']['name']);
                    
                    for($i = 0; $i < $file_count; $i++) {
                        if($_FILES['attachments']['error'][$i] == 0) {
                            $file_name = $_FILES['attachments']['name'][$i];
                            $file_temp = $_FILES['attachments']['tmp_name'][$i];
                            $file_type = $_FILES['attachments']['type'][$i];
                            
                            // Generate a unique file name to prevent overwriting
                            $file_path = $upload_dir . uniqid() . '_' . $file_name;
                            
                            // Move the file from temp location to our upload folder
                            if(move_uploaded_file($file_temp, $file_path)) {
                                // Insert file info into the database
                                $attachment_sql = "INSERT INTO attachments (question_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)";
                                $attachment_stmt = mysqli_prepare($conn, $attachment_sql);
                                
                                mysqli_stmt_bind_param($attachment_stmt, "isss", $question_id, $file_name, $file_path, $file_type);
                                mysqli_stmt_execute($attachment_stmt);
                                mysqli_stmt_close($attachment_stmt);
                            }
                        }
                    }
                }
                
                // Set success message
                $success_msg = "Your question has been posted successfully! Redirecting to the question page...";
                
                // Redirect after 2 seconds
                header("refresh:2;url=view_question.php?id=" . $question_id);
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
    <title>Ask a Question</title>
    <link rel="stylesheet" href="../css/ask_question.css">
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
                    <li><a href="forum.php" class="active">Q&A Forum</a></li>
                    <?php if($_SESSION["user_type"] == 'student') { ?>
                    <li><a href="tutors.php">Find Tutors</a></li>
                    <li><a href="my_sessions.php">Sessions</a></li>
                    <?php } else { ?>
                    <li><a href="my_students.php">Students</a></li>
                    <li><a href="schedule.php">Schedule</a></li>
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
                <h1>Ask a Question</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <div class="question-form-container">
                <?php 
                if(!empty($success_msg)){
                    echo '<div class="alert alert-success">' . $success_msg . '</div>';
                }
                ?>
                
                <form class="question-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Question Title</label>
                        <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>" placeholder="Be specific and clear about what you're asking">
                        <span class="invalid-feedback"><?php echo $title_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control <?php echo (!empty($subject_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $subject; ?>" placeholder="E.g., Mathematics, Physics, History, etc." list="subjects">
                        <datalist id="subjects">
                            <?php
                            foreach($popular_subjects as $subject) {
                                echo '<option value="' . htmlspecialchars($subject) . '">';
                            }
                            ?>
                        </datalist>
                        <span class="invalid-feedback"><?php echo $subject_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Question Details</label>
                        <textarea name="content" class="form-control <?php echo (!empty($content_err)) ? 'is-invalid' : ''; ?>" rows="8" placeholder="Explain your question in detail. Be clear and provide context to help others understand your question better."><?php echo $content; ?></textarea>
                        <span class="invalid-feedback"><?php echo $content_err; ?></span>
                    </div>
                    
                    <div class="form-group file-uploads">
                        <label>Attachments (Optional)</label>
                        <div class="file-input-container">
                            <input type="file" name="attachments[]" id="attachments" class="file-input" multiple>
                            <label for="attachments" class="file-label">Choose Files</label>
                            <span class="selected-files">No files selected</span>
                        </div>
                        <p class="file-help">You can upload images, PDFs, or documents to help explain your question. Maximum 5 files, 2MB each.</p>
                    </div>
                    
                    <div class="form-buttons">
                        <a href="forum.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Post Question</button>
                    </div>
                </form>
                
                <div class="question-tips">
                    <h3>Tips for a great question</h3>
                    <ul>
                        <li><strong>Be specific.</strong> Include details about what you're trying to understand.</li>
                        <li><strong>Be clear.</strong> Explain where you're stuck and what you've tried.</li>
                        <li><strong>Keep it concise.</strong> Include only relevant information.</li>
                        <li><strong>Check for typos.</strong> A well-written question gets better answers.</li>
                        <li><strong>Add context.</strong> Explain why you're asking and how it relates to your studies.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // File upload display
        document.getElementById('attachments').addEventListener('change', function(e) {
            const fileCount = e.target.files.length;
            const selectedFiles = document.querySelector('.selected-files');
            
            if(fileCount > 0) {
                selectedFiles.textContent = fileCount === 1 
                    ? '1 file selected' 
                    : `${fileCount} files selected`;
            } else {
                selectedFiles.textContent = 'No files selected';
            }
        });
    </script>
    <script src="../js/main.js"></script>
</body>
</html>
 