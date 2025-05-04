<?php
/**
  * Email service for Sage Education Platform
 * 
 * This file provides functions to send emails using SendGrid API
 */

/**
 * Send an email using the SendGrid API
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $html_content Email message in HTML format
 * @param string $from_email From email address
 * @param string $from_name From name
 * @return array Response from the API
 */
function send_email($to, $subject, $html_content, $from_email = "noreply@sage-edu.com", $from_name = "Sage Education") {
    // URL to the proxy server
    $proxy_url = "https://hooks.jdoodle.net/proxy?url=https://api.sendgrid.com/v3/mail/send";
    
    // Prepare the payload for SendGrid API
    $payload = [
        "personalizations" => [
            [
                "to" => [
                    ["email" => $to]
                ],
                "subject" => $subject
            ]
        ],
        "from" => [
            "email" => $from_email,
            "name" => $from_name
        ],
        "content" => [
            [
                "type" => "text/html",
                "value" => $html_content
            ]
        ]
    ];
    
    // For development/testing, display the email content
    echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 20px; background: #f9f9f9;'>";
    echo "<h3>Email would be sent with the following details:</h3>";
    echo "<p><strong>To:</strong> " . $to . "</p>";
    echo "<p><strong>Subject:</strong> " . $subject . "</p>";
    echo "<p><strong>Message:</strong></p>";
    echo $html_content;
    echo "</div>";
    
    // Send the request to the proxy server
    $ch = curl_init($proxy_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        return [
            'success' => false,
            'error' => $err
        ];
    }
    
    return [
        'success' => true,
        'response' => $response
    ];
}
 