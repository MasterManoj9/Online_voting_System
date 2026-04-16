<?php
session_start();
include "config.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Delete any votes cast by this voter first
    $conn->query("DELETE FROM votes WHERE user_id='$id'");
    // Delete the voter
    $conn->query("DELETE FROM users WHERE id='$id' AND role='voter'");
}

header("Location: manage_voters.php");
exit();
?>
