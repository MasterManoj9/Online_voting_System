<?php
session_start();
include "config.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $party = $_POST['party'];
    $manifesto = $_POST['manifesto'];
    
    $targetPath = "";
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        if (!file_exists("uploads")) { mkdir("uploads", 0777, true); }
        $img = time()."_".basename($_FILES['image']['name']);
        $targetPath = "uploads/".$img;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
    }

    $conn->query("INSERT INTO candidates (name, party, manifesto, image) VALUES ('$name','$party','$manifesto','$targetPath')");
    echo "<script>alert('Candidate Added Successfully!'); window.location='candidates.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Candidate</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "navbar.php"; ?>
<div class="container">
    <a href="candidates.php" class="back-link">⬅ Back to Candidates</a>
    <div class="login-container" style="max-width: 600px;">
        <h2>Add New Candidate &nbsp;🙋🏻‍♂️</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" placeholder="Candidate Full Name" required>
            <input type="text" name="party" placeholder="Party / Affiliation" required>
            <textarea name="manifesto" placeholder="Manifesto / Promises" rows="4"></textarea>
            
            <div style="text-align: left; margin-top: 10px;">
                <label style="font-size: 0.9rem; color: #6B7280; font-weight: 600;">Candidate Photo:</label>
                <input type="file" name="image" accept="image/*" style="padding: 10px 0;">
            </div>
            
            <button type="submit" name="submit" style="margin-top: 10px;">➕ Save Candidate</button>
        </form>
    </div>
</div>
</body>
</html>
