<?php
// ==============================
// ✅ VERIFY OTP API
// Called via AJAX from frontend
// ==============================

header('Content-Type: application/json');
include "config.php";

$email = trim($_POST['email'] ?? '');
$otp   = trim($_POST['otp'] ?? '');

if (!$email || !$otp) {
    echo json_encode(['success' => false, 'message' => 'Email and OTP are required.']);
    exit();
}

// Check OTP in database
$stmt = $conn->prepare("SELECT * FROM otp_verification WHERE email = ? AND otp_code = ? AND is_verified = 0 ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("ss", $email, $otp);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Check if OTP has expired
    if (strtotime($row['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
        exit();
    }

    // Mark OTP as verified
    $stmt2 = $conn->prepare("UPDATE otp_verification SET is_verified = 1 WHERE id = ?");
    $stmt2->bind_param("i", $row['id']);
    $stmt2->execute();

    echo json_encode(['success' => true, 'message' => 'OTP verified successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
}
?>
