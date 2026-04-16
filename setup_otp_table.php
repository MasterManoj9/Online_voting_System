<?php
// ==============================
// 🗄 FIX OTP VERIFICATION TABLE
// ==============================

include "config.php";

// Drop the old table
$conn->query("DROP TABLE IF EXISTS otp_verification");
echo "🗑️ Old otp_verification table dropped.<br>";

// Create with correct columns - using DATETIME instead of TIMESTAMP to avoid MySQL issues
$sql = "CREATE TABLE otp_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_verified TINYINT(1) DEFAULT 0
)";

if ($conn->query($sql)) {
    echo "✅ otp_verification table created successfully!<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// Ensure email column exists in users table
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN email VARCHAR(255) AFTER phone");
    echo "✅ Email column added to users table!<br>";
} else {
    echo "ℹ️ Email column already exists in users table.<br>";
}

echo "<br>✅ All done! <a href='index.html'>Go back to the app</a>";
?>
