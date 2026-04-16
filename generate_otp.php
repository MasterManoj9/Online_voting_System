<?php
// ==============================
// 🔑 GENERATE OTP API
// Called via AJAX from frontend
// Returns OTP to be sent via EmailJS
// ==============================

header('Content-Type: application/json');
include "config.php";

$email = trim($_POST['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

// Delete any existing OTPs for this email (cleanup)
$stmt = $conn->prepare("DELETE FROM otp_verification WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

// Generate 6-digit OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Set expiration to 5 minutes from now
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Store OTP in database
$stmt = $conn->prepare("INSERT INTO otp_verification (email, otp_code, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $otp, $expires_at);

if ($stmt->execute()) {
    // Return OTP to frontend so EmailJS can send it
    echo json_encode([
        'success' => true,
        'otp' => $otp,
        'message' => 'OTP generated successfully.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to generate OTP.']);
}
?>
