<?php
session_start();
include "config.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}
$result = $conn->query("SELECT * FROM candidates");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; flex-wrap: wrap; gap: 10px;">
        <h2 class="title" style="margin-bottom: 0;">Candidates Directory &nbsp;👥</h2>
        <a href="add_candidate.php" class="btn add" style="margin-bottom: 0;">➕ Add New Candidate</a>
    </div>
    <hr>
    
    <div class="grid">
        <?php while($row = $result->fetch_assoc()) { ?>
        <div class="card">
            <?php if (!empty($row['image'])) { ?>
                <img src="<?= htmlspecialchars($row['image']) ?>" alt="Image">
            <?php } ?>
            <h3><?= htmlspecialchars($row['name']) ?></h3>
            <p><b><?= htmlspecialchars($row['party']) ?></b></p>
            <p style="margin: 15px 0; font-size: 0.85rem; color: var(--text-muted); text-align: justify;">
                <?= htmlspecialchars(substr($row['manifesto'], 0, 80)) ?>...
            </p>
            <div style="display: flex; justify-content: center; gap: 10px; margin-top: 15px;">
                <a class="btn edit" href="edit_candidate.php?id=<?= $row['id'] ?>">✂️ Edit</a>
                <a class="btn delete" href="delete_candidate.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete <?= htmlspecialchars($row['name']) ?>?')">🛑 Delete</a>
            </div>
        </div>
        <?php } ?>
    </div>
    
    <?php if ($result->num_rows == 0) { ?>
        <p style="text-align:center; padding: 40px; color: var(--text-muted);">No candidates added yet.</p>
    <?php } ?>
</div>
</body>
</html>
