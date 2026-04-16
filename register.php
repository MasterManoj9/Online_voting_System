<?php
session_start();
include "config.php";

// ==============================
// 🔍 DEBUG LOGGER
// ==============================
function logDebug($msg) {
    file_put_contents(__DIR__ . "/register_debug.log", date("H:i:s") . " → " . $msg . "\n", FILE_APPEND);
}

logDebug("=== REGISTRATION ATTEMPT STARTED ===");

// ==============================
// 📥 GET FORM DATA
// ==============================

$name         = $_POST['name'] ?? '';
$phone        = $_POST['phone'] ?? '';
$email        = $_POST['email'] ?? '';
$aadhaar      = $_POST['aadhaar'] ?? '';
$address      = $_POST['address'] ?? '';
$password     = $_POST['password'] ?? '';
$otp_verified  = $_POST['otp_verified'] ?? '0';
$face_verified = $_POST['face_verified'] ?? '0';

logDebug("Name=$name | Phone=$phone | Email=$email | Aadhaar=$aadhaar | OTP_Verified=$otp_verified");

// ==============================
// ✅ VALIDATION
// ==============================

if (!$name || !$phone || !$email || !$aadhaar || !$address || !$password) {
    logDebug("FAIL: Missing required fields");
    echo "<script>alert('All fields are required!'); window.location.href='index.html';</script>";
    exit();
}

if (strlen($aadhaar) != 12 || !is_numeric($aadhaar)) {
    logDebug("FAIL: Invalid Aadhaar - " . $aadhaar);
    echo "<script>alert('Invalid Aadhaar number!'); window.location.href='index.html';</script>";
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logDebug("FAIL: Invalid email - " . $email);
    echo "<script>alert('Invalid email address!'); window.location.href='index.html';</script>";
    exit();
}

// ==============================
// 🔒 CHECK FACE VERIFICATION
// ==============================

if ($face_verified !== '1') {
    echo "<script>alert('Face verification is required. Please complete face verification during registration.'); window.location.href='index.html';</script>";
    exit();
}

// ==============================
// 📧 CHECK OTP VERIFICATION (hidden field)
// ==============================

if ($otp_verified !== '1') {
    logDebug("FAIL: otp_verified field = $otp_verified (not 1)");
    echo "<script>alert('Please verify your email with OTP first!'); window.location.href='index.html';</script>";
    exit();
}

// ====================================
// 📧 DOUBLE-CHECK OTP IN DATABASE
// ====================================

$otp_check = $conn->prepare("SELECT * FROM otp_verification WHERE email = ? AND is_verified = 1 ORDER BY created_at DESC LIMIT 1");
$otp_check->bind_param("s", $email);
$otp_check->execute();
$otp_result = $otp_check->get_result();

logDebug("DB OTP check for $email → rows found: " . $otp_result->num_rows);

if ($otp_result->num_rows == 0) {
    logDebug("FAIL: No verified OTP record in DB for $email");
    echo "<script>alert('OTP verification record not found in DB. Please verify OTP again.'); window.location.href='index.html';</script>";
    exit();
}

// ==============================
// 🔒 CHECK DUPLICATE USER
// ==============================

$check = $conn->query("SELECT id FROM users WHERE phone='$phone' OR aadhaar='$aadhaar' OR email='$email'");
logDebug("Duplicate check → rows: " . $check->num_rows);

if ($check->num_rows > 0) {
    logDebug("FAIL: Duplicate user (phone/aadhaar/email already exists)");
    echo "<script>alert('User already exists with this phone, Aadhaar, or email!'); window.location.href='index.html';</script>";
    exit();
}

// ==============================
// 🔑 HASH PASSWORD
// ==============================

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// ==============================
// 🖼 IMAGE UPLOAD
// ==============================

$imagePath = 'uploads/default.png'; // Default if no image

if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    if (!file_exists("uploads")) {
        mkdir("uploads", 0777, true);
    }

    $imageName = time() . "_" . basename($_FILES['image']['name']);
    $targetPath = "uploads/" . $imageName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        $imagePath = $targetPath;
        logDebug("Image uploaded: $targetPath");
    } else {
        logDebug("WARNING: Image upload failed, using default");
    }
} else {
    $errCode = $_FILES['image']['error'] ?? 'no file';
    logDebug("WARNING: No image or error code=$errCode, using default");
}

// ==============================
// 📝 INSERT INTO DATABASE
// ==============================

$sql = "INSERT INTO users (name, phone, email, aadhaar, address, password, role, image, status, face_verified)
        VALUES ('$name', '$phone', '$email', '$aadhaar', '$address', '$hashed_password', 'voter', '$imagePath', 'approved', '$face_verified')";

logDebug("Running INSERT for $name ($email)");

if ($conn->query($sql)) {
    logDebug("SUCCESS: User inserted. ID=" . $conn->insert_id);

    // Clean up OTP records
    $del_stmt = $conn->prepare("DELETE FROM otp_verification WHERE email = ?");
    $del_stmt->bind_param("s", $email);
    $del_stmt->execute();
    logDebug("OTP records cleaned up for $email");

    echo "
    <script>
        window.location.href = 'index.html?success=1';
    </script>
    ";
} else {
    logDebug("FAIL: INSERT error → " . $conn->error);
    echo "<script>alert('Registration failed: " . addslashes($conn->error) . "'); window.location.href='index.html';</script>";
}
?>
