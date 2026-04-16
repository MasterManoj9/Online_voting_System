<?php
session_start();
include "config.php";

// 🔐 ADMIN ONLY
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

// 1. Delete uploaded candidate images
$images = $conn->query("SELECT image FROM candidates WHERE image IS NOT NULL AND image != ''");
while ($img = $images->fetch_assoc()) {
    $path = $img['image'];
    if (file_exists($path)) {
        unlink($path);
    }
}

// 2. Truncate votes table
$conn->query("TRUNCATE TABLE votes");

// 3. Truncate candidates table
$conn->query("TRUNCATE TABLE candidates");

// 4. Reset voters' status back to default (approved but not voted)
$conn->query("UPDATE users SET status='approved' WHERE role='voter' AND status='voted'");

// 5. Turn election off
$conn->query("UPDATE settings SET election_status='OFF' WHERE id=1");

// 6. Reset election title
$conn->query("UPDATE settings SET election_title='Online Voting System' WHERE id=1");

echo "<script>alert('Election has been fully reset. All votes, candidates, and settings have been cleared.'); window.location='admin_dashboard.php';</script>";
?>
