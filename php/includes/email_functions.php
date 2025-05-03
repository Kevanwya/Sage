<?php
//  Email functions for Sage education platform

/**
 * Send password reset email
 * 
 * @param string $email User's email address
 * @param string $token Reset token
 * @return bool True if email was sent successfully
 */
function sendPasswordResetEmail($email, $token) {
    $subject = "Sage - Password Reset";
    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . "/new_password.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Reset Your Sage Password</title>
    </head>
    <body>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
            <div style='background-color: #0ea5e9; padding: 20px; text-align: center; color: white;'>
                <h1>Sage Password Reset</h1>
            </div>
            <div style='padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0;'>
                <p>Hello,</p>
                <p>We received a request to reset your password for your Sage account. Click the button below to reset it:</p>
                <p style='text-align: center;'>
                    <a href='{$reset_link}' style='display: inline-block; background-color: #0ea5e9; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                </p>
                <p>If you didn't request this, you can safely ignore this email. Your password will not be changed.</p>
                <p>If the button above doesn't work, copy and paste the following link into your browser:</p>
                <p style='word-break: break-all;'>{$reset_link}</p>
                <p>This link will expire in 1 hour for security reasons.</p>
            </div>
            <div style='text-align: center; margin-top: 20px; color: #64748b; font-size: 12px;'>
                <p>© 2023 Sage. All rights reserved.</p>
                <p>Academic support platform for secondary school students in St. Lucia</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Set content-type header for sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Sage <noreply@sage-edu.com>' . "\r\n";
    
    // Send email
    return mail($email, $subject, $message, $headers);
}

/**
 * Send account registration confirmation email
 * 
 * @param string $email User's email address
 * @param string $username User's username
 * @param string $fullName User's full name
 * @return bool True if email was sent successfully
 */
function sendRegistrationEmail($email, $username, $fullName) {
    $subject = "Welcome to Sage - Account Created Successfully";
    $login_link = "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . "/login.php";
    
    $message = "
    <html>
    <head>
        <title>Welcome to Sage</title>
    </head>
    <body>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
            <div style='background-color: #0ea5e9; padding: 20px; text-align: center; color: white;'>
                <h1>Welcome to Sage!</h1>
            </div>
            <div style='padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0;'>
                <p>Hello {$fullName},</p>
                <p>Thank you for creating an account with Sage, the academic support platform for students in St. Lucia.</p>
                <p>Your account has been successfully created with the username: <strong>{$username}</strong></p>
                <p>You can now log in to access all our features:</p>
                <p style='text-align: center;'>
                    <a href='{$login_link}' style='display: inline-block; background-color: #0ea5e9; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Log In to Your Account</a>
                </p>
                <p>With Sage, you can:</p>
                <ul>
                    <li>Ask academic questions and get answers from qualified tutors</li>
                    <li>Book personalized tutoring sessions</li>
                    <li>Get help with homework and assignments</li>
                    <li>Prepare for CSEC examinations</li>
                </ul>
                <p>If you have any questions, feel free to reply to this email or contact our support team.</p>
            </div>
            <div style='text-align: center; margin-top: 20px; color: #64748b; font-size: 12px;'>
                <p>© 2023 Sage. All rights reserved.</p>
                <p>Academic support platform for students in St. Lucia</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Set content-type header for sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Sage <noreply@sage-edu.com>' . "\r\n";
    
    // Send email
    return mail($email, $subject, $message, $headers);
}

/**
 * Send session booking confirmation
 * 
 * @param array $session Session details
 * @param array $student Student details
 * @param array $tutor Tutor details
 * @return bool True if email was sent successfully
 */
function sendSessionConfirmationEmail($session, $student, $tutor) {
    $subject = "Sage - Tutoring Session Confirmed";
    $session_link = "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . "/view_session.php?id=" . $session['id'];
    
    // Format date and time
    $date = date('l, F j, Y', strtotime($session['session_date']));
    $start_time = date('g:i A', strtotime($session['start_time']));
    $end_time = date('g:i A', strtotime($session['end_time']));
    
    $message = "
    <html>
    <head>
        <title>Tutoring Session Confirmation</title>
    </head>
    <body>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
            <div style='background-color: #0ea5e9; padding: 20px; text-align: center; color: white;'>
                <h1>Tutoring Session Confirmed</h1>
            </div>
            <div style='padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0;'>
                <p>Hello {$student['full_name']},</p>
                <p>Your tutoring session has been confirmed with the following details:</p>
                
                <div style='background-color: #e0f2fe; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p><strong>Subject:</strong> {$session['subject']}</p>
                    <p><strong>Date:</strong> {$date}</p>
                    <p><strong>Time:</strong> {$start_time} - {$end_time}</p>
                    <p><strong>Tutor:</strong> {$tutor['full_name']}</p>
                </div>
                
                <p>You can view all details and join the session by clicking the button below:</p>
                <p style='text-align: center;'>
                    <a href='{$session_link}' style='display: inline-block; background-color: #0ea5e9; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>View Session</a>
                </p>
                <p>If you need to reschedule or cancel this session, please do so at least 24 hours in advance.</p>
            </div>
            <div style='text-align: center; margin-top: 20px; color: #64748b; font-size: 12px;'>
                <p>© 2023 Sage. All rights reserved.</p>
                <p>Academic support platform for students in St. Lucia</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Set content-type header for sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Sage <noreply@sage-edu.com>' . "\r\n";
    
    // Send email
    return mail($student['email'], $subject, $message, $headers);
}
 