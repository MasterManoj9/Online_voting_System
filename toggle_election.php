<?php
session_start();
include "config.php";

// 🔐 ADMIN ONLY
if ($_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

// GET CURRENT STATUS
$result = $conn->query("SELECT election_status FROM settings WHERE id=1");
$row = $result->fetch_assoc();

$new_status = ($row['election_status'] == 'ON') ? 'OFF' : 'ON';

// UPDATE
$conn->query("UPDATE settings SET election_status='$new_status' WHERE id=1");

header("Location: admin_dashboard.php");
?>
