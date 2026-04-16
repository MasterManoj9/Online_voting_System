<?php
session_start();
include "config.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}
$result = $conn->query("SELECT * FROM users WHERE role='voter'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Voters</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container">
    <h2 class="title" style="margin-top: 20px;">Manage Voters &nbsp;👥</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Approve new user registrations and monitor voting statuses.</p>

    <div style="overflow-x: auto;">
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Aadhaar</th>
                <th>Vote Status</th>
                <th>Action</th>
            </tr>
            <?php while($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['aadhaar']) ?></td>
                <td>
                    <?php if($row['status'] == 'voted') { ?>
                        <span class="badge badge-success">✔ Voted</span>
                    <?php } else { ?>
                        <span class="badge badge-warning">⏳ Not Voted</span>
                    <?php } ?>
                </td>
                <td>
                    <a href="delete_voter.php?id=<?= $row['id'] ?>" class="btn delete" onclick="return confirm('Are you sure you want to remove this voter?')">🗑 Remove</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>
</body>
</html>
