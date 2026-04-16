<?php

// ==============================
// 🗄 DATABASE CONFIGURATION
// ==============================

$servername = "localhost";   // Usually localhost in XAMPP
$username   = "root";        // Default XAMPP username
$password   = "";            // Default password is empty
$database   = "online_voting"; // Your database name

// ==============================
// 🔌 CREATE CONNECTION
// ==============================

$conn = new mysqli($servername, $username, $password, $database, 3307);

// ==============================
// ❌ CHECK CONNECTION
// ==============================

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ==============================
// ✅ SET CHARSET (IMPORTANT)
// ==============================

$conn->set_charset("utf8mb4");

?>
