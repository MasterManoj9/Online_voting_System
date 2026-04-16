<?php
session_start();
include "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id='$user_id'")->fetch_assoc();
$status = $conn->query("SELECT election_status FROM settings WHERE id=1")->fetch_assoc()['election_status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container">
    <h2 class="title">Welcome, <?= htmlspecialchars($user['name']) ?>! &nbsp;🎉</h2>
    <div class="cards">
        <div class="card">
            <h3>Your Voting Status</h3>
            <p class="status-indicator <?= ($user['status']=='voted') ? 'on' : 'off' ?>">
                <?= ($user['status']=='voted') ? "✔ Voted" : "❌ Not Voted" ?>
            </p>
        </div>
        <div class="card">
            <h3>Election Status</h3>
            <p class="status-indicator <?= ($status=='ON') ? 'on' : 'off' ?>">
                <?= ($status=='ON') ? "🟢 Active" : "🔴 Closed" ?>
            </p>
        </div>
    </div>
    <div class="section" style="text-align: center;">
        <?php if ($status=='ON') { 
            if ($user['status']!='voted') { ?>
                <h3>Ready to make your voice heard?</h3><br>
                <a href="vote.php" class="btn add" style="font-size: 1.1rem; padding: 15px 30px;">🗳️ Cast Your Vote Now</a>
            <?php } else { ?>
                <h3>Thank you for voting!</h3>
                <p style="margin-top: 10px; color: var(--text-muted);">Your vote has been successfully anonymized and recorded.</p>
            <?php }
        } else { ?>
            <h3>Voting is currently closed.</h3>
            <p style="margin-top: 10px; color: var(--text-muted);">Please wait for the administrator to start the election.</p>
        <?php } ?>
    </div>

    <!-- Quick Actions -->
    <div class="cards" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-top: 20px;">
        <a href="profile.php" style="text-decoration: none;">
            <div class="card" style="cursor: pointer;">
                <h1 style="font-size: 2rem;">👤</h1>
                <h3>My Profile</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">View details & update photo</p>
            </div>
        </a>
        <div class="card" style="cursor: pointer;" onclick="window.location='profile.php#download'">
            <h1 style="font-size: 2rem;">📄</h1>
            <h3>Voter ID Card</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted);">Download your ID as PDF</p>
        </div>
    </div>
</div>
</body>
</html>
