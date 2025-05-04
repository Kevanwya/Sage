<?php
//  Email sending function for Sage education platform

/**
 * Send an email using PHP's mail function
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email message (HTML)
 * @param string $from From email address
 * @return bool True if email was sent successfully
 */
function send_email($to, $subject, $message, $from = "noreply@sage-edu.com") {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Sage <' . $from . '>' . "\r\n";
    
    // For demonstration, show the email that would be sent
    echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 20px; background: #f9f9f9;'>";
    echo "<h3>Email would be sent with the following details:</h3>";
    echo "<p><strong>To:</strong> " . $to . "</p>";
    echo "<p><strong>Subject:</strong> " . $subject . "</p>";
    echo "<p><strong>Message:</strong></p>";
    echo $message;
    echo "</div>";
    
    // In production, uncomment this line to actually send the email
    // return mail($to, $subject, $message, $headers);
    
    return true;
}
 