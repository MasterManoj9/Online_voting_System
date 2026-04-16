<?php
include "config.php";
$settings = $conn->query("SELECT election_title FROM settings WHERE id=1")->fetch_assoc();
header('Content-Type: application/json');
echo json_encode(["title" => $settings['election_title'] ?? 'Online Voting System']);
?>
