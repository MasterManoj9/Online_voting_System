<?php
session_start();
include "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['title'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $conn->query("UPDATE settings SET election_title='$title' WHERE id=1");
    echo "<script>alert('Election Title updated successfully!'); window.location='admin_dashboard.php';</script>";
} else {
    header("Location: admin_dashboard.php");
}
?>
