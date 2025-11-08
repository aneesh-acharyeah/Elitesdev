<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

// Allow CORS for testing
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Get JSON POST data
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Check if data is valid JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON data"]);
    exit;
}

// Form input validation
$requiredFields = ['fname', 'email', 'message'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing required field: $field"]);
        exit;
    }
}

// Extract variables with sanitization
$fname = htmlspecialchars($data['fname']);
$lname = isset($data['lname']) ? htmlspecialchars($data['lname']) : '';
$phone = isset($data['phone']) ? htmlspecialchars($data['phone']) : 'Not provided';
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$message = nl2br(htmlspecialchars($data['message']));
$company = isset($data['company']) ? htmlspecialchars($data['company']) : 'Not provided';
$service = isset($data['service']) ? htmlspecialchars($data['service']) : 'Not specified';

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid email address"]);
    exit;
}

// Setup mail
$mail = new PHPMailer(true);

try {
    // SMTP settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = '';
    $mail->Password = '';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Email content
    $mail->setFrom($email, "$fname $lname");
    $mail->addAddress('');
    $mail->addReplyTo($email, "$fname $lname");
    $mail->isHTML(true);
    $mail->Subject = "$fname $lname has an enquiry regarding- $service";

    // HTML Body with enhanced styling
    $mail->Body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #000000, #333); padding: 30px; text-align: center; color: white; }
            .logo { max-width: 200px; margin-bottom: 15px; }
            .content { padding: 30px; }
            .field { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
            .field-label { font-weight: bold; color: #333; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
            .field-value { color: #666; font-size: 16px; line-height: 1.5; }
            .message-box { background: #f9f9f9; padding: 20px; border-left: 4px solid #FFD700; border-radius: 4px; margin-top: 10px; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div style='font-size: 24px; font-weight: bold; margin-bottom: 10px;'>ELITESDEV</div>
                <div style='font-size: 16px; opacity: 0.9;'>New Contact Form Submission</div>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='field-label'>Name</div>
                    <div class='field-value'>{$fname} {$lname}</div>
                </div>
                <div class='field'>
                    <div class='field-label'>Contact Information</div>
                    <div class='field-value'>
                        <strong>Email:</strong> {$email}<br>
                        <strong>Phone:</strong> {$phone}
                    </div>
                </div>
                <div class='field'>
                    <div class='field-label'>Company</div>
                    <div class='field-value'>{$company}</div>
                </div>
                <div class='field'>
                    <div class='field-label'>Service Interested In</div>
                    <div class='field-value' style='color: #FFD700; font-weight: bold;'>{$service}</div>
                </div>
                <div class='field'>
                    <div class='field-label'>Message</div>
                    <div class='message-box'>{$message}</div>
                </div>
            </div>
            <div class='footer'>
                This message was sent from your website contact form on " . date('F j, Y \a\t g:i A') . "
            </div>
        </div>
    </body>
    </html>
    ";


    $mail->send();
    echo json_encode(["status" => "success", "message" => "Email sent successfully! We'll get back to you soon."]);

} catch (Exception $e) {
    error_log("Mailer Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to send email. Please try again later."]);
}
?>
