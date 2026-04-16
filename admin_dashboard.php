<?php
session_start();
include "config.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

$total_voters = $conn->query("SELECT COUNT(*) as t FROM users WHERE role='voter'")->fetch_assoc()['t'];
$total_candidates = $conn->query("SELECT COUNT(*) as t FROM candidates")->fetch_assoc()['t'];
$total_votes = $conn->query("SELECT COUNT(*) as t FROM votes")->fetch_assoc()['t'];
$turnout = ($total_voters > 0) ? round(($total_votes/$total_voters)*100) : 0;
$settings = $conn->query("SELECT election_status, election_title FROM settings WHERE id=1")->fetch_assoc();
$status = $settings['election_status'];
$title = $settings['election_title'] ?? 'Online Voting System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container">
    <h2 class="title">Admin Overview ⚙️</h2>
    <p style="color: var(--text-muted);">Monitor real-time election metrics and control global settings.</p>

    <div class="cards">
        <div class="card">
            <h1><?= $total_voters ?></h1>
            <p>Registered Voters</p>
        </div>
        <div class="card">
            <h1><?= $total_candidates ?></h1>
            <p>Total Candidates</p>
        </div>
        <div class="card">
            <h1><?= $total_votes ?></h1>
            <p>Votes Cast</p>
        </div>
        <div class="card">
            <h1><?= $turnout ?>%</h1>
            <p>Voter Turnout</p>
        </div>
    </div>

    <div class="section" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <h3>Election Control &nbsp;🚦</h3>
            <p style="margin-top: 10px; color: var(--text-muted);">Current Status: <strong class="status-indicator <?= ($status=='ON')?'on':'off' ?>"><?= ($status=='ON')?'🟢 Active':'🔴 Closed' ?></strong></p>
            <form action="update_title.php" method="POST" style="margin-top:15px; display:inline-flex; gap:10px; align-items:center;">
                <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" required style="padding: 8px 12px; margin:0; width:220px;" placeholder="Election Title">
                <button type="submit" class="start" style="padding: 8px 15px; margin:0; width:auto;">💾 Update Title</button>
            </form>
        </div>
        <div style="display: flex; gap: 15px;">
            <a href="toggle_election.php" style="text-decoration:none;">
                <button class="<?= ($status=='ON')?'stop':'start' ?>" style="width:auto;">
                    <?= ($status=='ON')?'⏹ Stop Election':'▶ Start Election' ?>
                </button>
            </a>
            <a href="reset.php" style="text-decoration:none;" onclick="return confirm('Are you sure you want to reset the election? This will delete all votes and reset voter statuses.')">
                <button class="stop" style="width:auto;">🔄 Reset Election</button>
            </a>
        </div>
    </div>
</div>
</body>
</html>
