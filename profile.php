<?php
session_start();
include "config.php";

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle profile image update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    if ($_FILES['profile_image']['error'] == 0) {
        if (!file_exists("uploads")) {
            mkdir("uploads", 0777, true);
        }
        $imageName = time() . "_profile_" . basename($_FILES['profile_image']['name']);
        $targetPath = "uploads/" . $imageName;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            $stmt = $conn->prepare("UPDATE users SET image = ? WHERE id = ?");
            $stmt->bind_param("si", $targetPath, $user_id);
            $stmt->execute();
            header("Location: profile.php?updated=1");
            exit();
        }
    }
}

// Fetch user data (fresh after any update)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-wrapper {
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .profile-card {
            background: var(--card-bg);
            border-radius: 24px;
            backdrop-filter: blur(14px);
            border: 1px solid var(--card-border);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.6s ease-out forwards;
        }
        .profile-header {
            background: linear-gradient(135deg, var(--primary), #8B5CF6);
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        .profile-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            margin-bottom: 15px;
            transition: var(--transition);
            cursor: pointer;
        }
        .profile-avatar:hover {
            transform: scale(1.08);
            box-shadow: 0 12px 35px rgba(0,0,0,0.3);
        }
        .profile-header h2 {
            color: white;
            font-size: 1.6rem;
            margin-bottom: 5px;
        }
        .profile-header .role-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 4px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            backdrop-filter: blur(8px);
        }
        .profile-body {
            padding: 30px;
        }
        .profile-info-row {
            display: flex;
            align-items: flex-start;
            padding: 16px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .profile-info-row:last-child {
            border-bottom: none;
        }
        .profile-info-label {
            width: 140px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }
        .profile-info-value {
            flex: 1;
            font-size: 1rem;
            font-weight: 500;
            color: var(--dark);
            word-break: break-word;
        }
        .profile-actions {
            padding: 20px 30px 30px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .upload-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .upload-form input[type="file"] {
            flex: 1;
            min-width: 200px;
        }
        .btn-upload {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary), #8B5CF6);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
            font-size: 0.9rem;
        }
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3);
        }
        .btn-logout {
            display: block;
            width: 100%;
            padding: 14px;
            background: rgba(239, 68, 68, 0.08);
            color: #DC2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 10px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: var(--transition);
            font-size: 1rem;
        }
        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.15);
            transform: translateY(-2px);
        }
        .btn-download-id {
            display: block;
            width: 100%;
            padding: 14px;
            background: rgba(16, 185, 129, 0.08);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 10px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
            font-family: inherit;
        }
        .btn-download-id:hover {
            background: rgba(16, 185, 129, 0.15);
            transform: translateY(-2px);
        }
        .update-success {
            background: #DEF7EC;
            color: #03543F;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
            animation: slideUp 0.4s ease-out;
        }
        .aadhaar-masked {
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="profile-wrapper">

    <?php if (isset($_GET['updated'])) { ?>
        <div class="update-success">✅ Profile photo updated successfully!</div>
    <?php } ?>

    <div class="profile-card">
        <!-- Header with Avatar -->
        <div class="profile-header">
            <?php
            $imageSrc = (!empty($user['image']) && file_exists($user['image'])) 
                ? htmlspecialchars($user['image']) 
                : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=random&size=130&bold=true';
            ?>
            <img src="<?= $imageSrc ?>" alt="Profile Photo" class="profile-avatar" 
                 onclick="document.getElementById('fileInput').click()" title="Click to change photo">
            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <span class="role-badge"><?= ucfirst($user['role']) ?></span>
        </div>

        <!-- Profile Details -->
        <div class="profile-body">
            <div class="profile-info-row">
                <span class="profile-info-label">📞 Phone</span>
                <span class="profile-info-value"><?= htmlspecialchars($user['phone']) ?></span>
            </div>

            <?php if (!empty($user['email'])) { ?>
            <div class="profile-info-row">
                <span class="profile-info-label">📧 Email</span>
                <span class="profile-info-value"><?= htmlspecialchars($user['email']) ?></span>
            </div>
            <?php } ?>

            <?php if (!empty($user['aadhaar'])) { ?>
            <div class="profile-info-row">
                <span class="profile-info-label">🪪 Aadhaar</span>
                <span class="profile-info-value aadhaar-masked">
                    XXXX-XXXX-<?= substr($user['aadhaar'], -4) ?>
                </span>
            </div>
            <?php } ?>

            <?php if (!empty($user['address'])) { ?>
            <div class="profile-info-row">
                <span class="profile-info-label">🏠 Address</span>
                <span class="profile-info-value"><?= htmlspecialchars($user['address']) ?></span>
            </div>
            <?php } ?>

            <div class="profile-info-row">
                <span class="profile-info-label">🗳️ Status</span>
                <span class="profile-info-value">
                    <?php if ($user['status'] == 'voted') { ?>
                        <span class="badge badge-success">✔ Voted</span>
                    <?php } else { ?>
                        <span class="badge badge-warning">⏳ Not Voted</span>
                    <?php } ?>
                </span>
            </div>
        </div>

        <!-- Actions -->
        <div class="profile-actions">
            <!-- Update Photo -->
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="file" name="profile_image" id="fileInput" accept="image/*" required 
                       onchange="document.getElementById('uploadPreview').src=URL.createObjectURL(this.files[0])">
                <button type="submit" class="btn-upload">📷 Update Photo</button>
            </form>

            <?php if ($role === 'voter') { ?>
            <!-- Download Voter ID (Voters only) -->
            <button type="button" class="btn-download-id" onclick="generateVoterID()">
                📄 Download Voter ID Card (PDF)
            </button>
            <?php } ?>

            <!-- Logout -->
            <a href="logout.php" class="btn-logout" onclick="return confirm('Are you sure you want to logout?')">
                🚪 Logout
            </a>
        </div>
    </div>
</div>

<!-- jsPDF for Voter ID Card Generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function generateVoterID() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: [86, 54] // Credit card size
    });

    // Card background
    doc.setFillColor(17, 24, 39); // Dark navy
    doc.roundedRect(0, 0, 86, 54, 3, 3, 'F');

    // Top accent bar
    const gradient = doc.setFillColor(79, 70, 229); // Indigo
    doc.rect(0, 0, 86, 14, 'F');

    // Title
    doc.setFont("helvetica", "bold");
    doc.setFontSize(7);
    doc.setTextColor(255, 255, 255);
    doc.text("ELECTION COMMISSION OF INDIA", 43, 5, { align: 'center' });

    doc.setFontSize(8);
    doc.text("VOTER IDENTITY CARD", 43, 10, { align: 'center' });

    // Voter details section
    doc.setFontSize(6);
    doc.setFont("helvetica", "normal");
    doc.setTextColor(200, 200, 210);
    
    let y = 20;
    doc.text("Name", 5, y);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(255, 255, 255);
    doc.text("<?= addslashes(htmlspecialchars($user['name'])) ?>", 30, y);

    y += 6;
    doc.setFont("helvetica", "normal");
    doc.setTextColor(200, 200, 210);
    doc.text("Phone", 5, y);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(255, 255, 255);
    doc.text("<?= addslashes(htmlspecialchars($user['phone'])) ?>", 30, y);

    <?php if (!empty($user['email'])) { ?>
    y += 6;
    doc.setFont("helvetica", "normal");
    doc.setTextColor(200, 200, 210);
    doc.text("Email", 5, y);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(5);
    doc.text("<?= addslashes(htmlspecialchars($user['email'])) ?>", 30, y);
    doc.setFontSize(6);
    <?php } ?>

    y += 6;
    doc.setFont("helvetica", "normal");
    doc.setTextColor(200, 200, 210);
    doc.text("Aadhaar", 5, y);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(255, 255, 255);
    doc.text("XXXX-XXXX-<?= substr($user['aadhaar'] ?? '0000', -4) ?>", 30, y);

    y += 6;
    doc.setFont("helvetica", "normal");
    doc.setTextColor(200, 200, 210);
    doc.text("Voter ID", 5, y);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(79, 70, 229);
    doc.text("VOT-<?= str_pad($user['id'], 6, '0', STR_PAD_LEFT) ?>", 30, y);

    // Footer
    doc.setFontSize(4);
    doc.setFont("helvetica", "normal");
    doc.setTextColor(150, 150, 160);
    doc.text("This is a digitally generated Voter ID card.", 43, 51, { align: 'center' });

    // Save
    doc.save("VoterID_<?= $user['name'] ?>.pdf");
}
</script>

</body>
</html>
