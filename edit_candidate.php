<?php
session_start();
include "config.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: index.html"); exit(); }

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM candidates WHERE id=$id");
$data = $result->fetch_assoc();

if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $party = $_POST['party'];
    $manifesto = $_POST['manifesto'];

    if (!empty($_FILES['image']['name'])) {
        $img = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $img);
        $conn->query("UPDATE candidates SET name='$name', party='$party', manifesto='$manifesto', image='uploads/$img' WHERE id=$id");
    } else {
        $conn->query("UPDATE candidates SET name='$name', party='$party', manifesto='$manifesto' WHERE id=$id");
    }
    echo "<script>alert('Updated Successfully!'); window.location='candidates.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Candidate</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "navbar.php"; ?>
<div class="container">
    <a href="candidates.php" class="back-link">⬅ Back to Candidates</a>
    <div class="login-container" style="max-width: 600px;">
        <h2>Edit Candidate &nbsp;✏️</h2>
        
        <?php if(!empty($data['image'])) { ?>
            <img src="<?= htmlspecialchars($data['image']) ?>" alt="Current" style="width:120px; height:120px; border-radius:50%; object-fit:cover; margin-bottom:20px; border:3px solid var(--primary); box-shadow: var(--shadow-sm);">
        <?php } ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" required>
            <input type="text" name="party" value="<?= htmlspecialchars($data['party']) ?>" required>
            <textarea name="manifesto" rows="4"><?= htmlspecialchars($data['manifesto']) ?></textarea>
            
            <div style="text-align: left; margin-top: 10px;">
                <label style="font-size: 0.9rem; color: #6B7280; font-weight: 600;">Update Photo (Leave blank to keep current):</label>
                <input type="file" name="image" accept="image/*" style="padding: 10px 0;">
            </div>
            
            <button type="submit" name="update" style="margin-top: 10px;">💾 Update Candidate</button>
        </form>
    </div>
</div>
</body>
</html>
