<?php
session_start();
include "config.php";

// 🔐 LOGIN CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$candidate_id = $_POST['candidate_id'];

// 🔴 CHECK ELECTION STATUS
$status_result = $conn->query("SELECT election_status FROM settings WHERE id=1");
$status = $status_result->fetch_assoc()['election_status'];

if ($status != 'ON') {
    echo "<script>
            alert('Voting is currently closed!');
            window.location='dashboard.php';
          </script>";
    exit();
}

// 🚫 PREVENT DOUBLE VOTING
$check = $conn->query("SELECT * FROM votes WHERE user_id='$user_id'");
if ($check->num_rows > 0) {
    echo "<script>
            alert('You already voted!');
            window.location='dashboard.php';
          </script>";
    exit();
}

// ✅ INSERT VOTE
$conn->query("INSERT INTO votes (user_id, candidate_id) VALUES ('$user_id', '$candidate_id')");

// ✅ UPDATE USER STATUS
$conn->query("UPDATE users SET status='voted' WHERE id='$user_id'");

echo "<script>
        alert('Vote submitted successfully!');
        window.location='dashboard.php';
      </script>";
?>
