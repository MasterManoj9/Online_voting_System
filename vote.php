<?php
session_start();
include "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
$user_id = $_SESSION['user_id'];
$checkVote = $conn->query("SELECT * FROM votes WHERE user_id='$user_id'");
if ($checkVote->num_rows > 0) {
    echo "<script>alert('You have already voted!'); window.location.href='dashboard.php';</script>";
    exit();
}
$status = $conn->query("SELECT election_status FROM settings WHERE id=1")->fetch_assoc()['election_status'];
$result = $conn->query("SELECT * FROM candidates");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Your Vote</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container">
    <a href="dashboard.php" class="back-link">⬅ Back to Dashboard</a>
    <h2 class="title" style="margin-top: 20px; text-align: center;">Choose Your Candidate &nbsp;🗳️</h2>
    
    <?php if ($status == 'OFF') { ?>
    <div class="section" style="text-align: center; padding: 60px 30px;">
        <h3 style="font-size: 1.5rem; color: var(--danger);">🚫 The election has not started yet.</h3>
        <p style="margin-top: 15px; color: var(--text-muted); font-size: 1.05rem;">This page shows the candidates after the elections are open. Please wait for the administrator to start the election.</p>
        <a href="dashboard.php" class="btn add" style="margin-top: 25px; font-size: 1rem; padding: 12px 25px;">🏠 Go to Dashboard</a>
    </div>
    <?php } else { ?>
    <div class="grid">
    <?php if ($result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <div class="card">
                <?php if (!empty($row['image'])) { ?>
                    <img src="<?= htmlspecialchars($row['image']) ?>" alt="Candidate Image">
                <?php } ?>
                <h3><?= htmlspecialchars($row['name']) ?></h3>
                <p><b>Party:</b> <span class="badge badge-success"><?= htmlspecialchars($row['party']) ?></span></p>
                <p style="margin: 15px 0; font-size: 0.9rem; color: var(--text-muted); text-align: justify;"><?= htmlspecialchars(substr($row['manifesto'], 0, 100)) ?><?= strlen($row['manifesto'])>100?'...':'' ?></p>
                <form action="submit_vote.php" method="POST" style="margin-top:15px;">
                    <input type="hidden" name="candidate_id" value="<?= $row['id'] ?>">
                    <button type="submit" onclick="return confirm('Register vote for <?= htmlspecialchars($row['name']) ?>?')">Vote for <?= htmlspecialchars($row['name']) ?></button>
                </form>
            </div>
        <?php } ?>
    <?php } else { ?>
        <p style="text-align: center; width: 100%;">No candidates available at the moment.</p>
    <?php } ?>
    </div>
    <?php } ?>
</div>
</body>
</html>
