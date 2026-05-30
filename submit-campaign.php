<?php
/**
 * Campaign Request Handler
 * Submit via POST - writes to Google Sheet and emails notification
 */

// Configuration
$googleSheetUrl = 'YOUR_GOOGLE_SHEET_WEB_APP_URL';
$notifyEmail = 'thelocanearbuy@gmail.com';
$fromEmail = 'noreply@thelocanearbuy.com';

// Get form data
$businessName = sanitize($_POST['businessName'] ?? '');
$contactName = sanitize($_POST['contactName'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$campaignType = sanitize($_POST['campaignType'] ?? '');
$message = sanitize($_POST['message'] ?? '');
$submitted = date('Y-m-d H:i:s');

// Validation
if (empty($businessName) || empty($contactName) || empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Required fields missing']);
    exit;
}

// Prepare data row
$data = [
    $submitted,
    $businessName,
    $contactName,
    $email,
    $phone,
    $campaignType,
    $message
];

// Write to Google Sheet (via Google Apps Script web app)
if ($googleSheetUrl !== 'YOUR_GOOGLE_SHEET_WEB_APP_URL') {
    $ch = curl_init($googleSheetUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'action' => 'append',
        'data' => json_encode($data)
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $sheetResult = curl_exec($ch);
    curl_close($ch);
}

// Send email notification
$subject = "New Campaign Request: $businessName";
$body = "New campaign request submitted:\n\n"
    . "Business: $businessName\n"
    . "Contact: $contactName\n"
    . "Email: $email\n"
    . "Phone: $phone\n"
    . "Campaign Type: $campaignType\n"
    . "Message: $message\n"
    . "Submitted: $submitted\n";

$headers = "From: $fromEmail\r\n";
$headers .= "Reply-To: $email\r\n";

mail($notifyEmail, $subject, $body, $headers);

// Also send confirmation to the submitter
$confirmSubject = "We got your request! - The Local NearBuy";
$confirmBody = "Hi $contactName,\n\n"
    . "Thanks for your interest in The Local NearBuy! We've received your campaign request and will be in touch within 24 hours to discuss the details.\n\n"
    . "What you submitted:\n"
    . "- Business: $businessName\n"
    . "- Campaign type: $campaignType\n\n"
    . "We look forward to working with you!\n\n"
    . "- The Local NearBuy Team";

mail($email, $confirmSubject, $confirmBody, "From: $fromEmail");

echo json_encode(['success' => true]);

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}