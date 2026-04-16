<?php
session_start();
include "config.php";

// ==============================
// 📥 GET DATA
// ==============================

$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

// ==============================
// ✅ VALIDATION
// ==============================

if (!$phone || !$password) {
    echo "<script>
            alert('Please fill all fields!');
            window.location.href='index.html';
          </script>";
    exit();
}

// ==============================
// 🔍 CHECK USER
// ==============================

$sql = "SELECT * FROM users WHERE phone='$phone'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    // ==============================
    // 🔐 VERIFY PASSWORD
    // ==============================
    if (password_verify($password, $user['password'])) {

        // ==============================
        // ✅ SET SESSION
        // ==============================
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // ==============================
        // 🔁 REDIRECT BASED ON ROLE
        // ==============================
        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }

        exit();

    } else {
        echo "<script>
                alert('Wrong password!');
                window.location.href='index.html';
              </script>";
    }

} else {
    echo "<script>
            alert('User not found!');
            window.location.href='index.html';
          </script>";
}
?>
