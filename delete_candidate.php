<?php
session_start();
include "config.php";

// 🔐 ADMIN CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

$id = $_GET['id'] ?? '';

// OPTIONAL: delete image file also
$result = $conn->query("SELECT image FROM candidates WHERE id=$id");
$data = $result->fetch_assoc();

if ($data && !empty($data['image']) && file_exists($data['image'])) {
    unlink($data['image']); // delete image file
}

// DELETE FROM DB
$conn->query("DELETE FROM candidates WHERE id=$id");

// REDIRECT
header("Location: admin_dashboard.php");
exit();
?>
