<?php
include "config.php";

// Add face_verified column to users table
$result = $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS face_verified TINYINT(1) DEFAULT 0");

if ($conn->error) {
    echo "<p style='color:red'>❌ Error: " . $conn->error . "</p>";
} else {
    echo "<p style='color:green'>✅ face_verified column added (or already exists) in users table.</p>";
}
echo "<p><a href='index.html'>← Go to Registration Page</a></p>";
?>
