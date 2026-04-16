<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
<?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') { ?>
    body { background: linear-gradient(135deg, #FFDAB9 0%, #FFECD2 50%, #FFF5EE 100%) !important; }
<?php } else { ?>
    body { background: linear-gradient(135deg, #B3E5FC 0%, #E1F5FE 50%, #F0F9FF 100%) !important; }
<?php } ?>
</style>
<div class="navbar">
<?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') { ?>
    <a href="admin_dashboard.php" class="<?= ($current_page=='admin_dashboard.php')?'active':'' ?>">🏠 Dashboard</a>
    <a href="candidates.php" class="<?= ($current_page=='candidates.php')?'active':'' ?>">👥 Candidates</a>
    <a href="manage_voters.php" class="<?= ($current_page=='manage_voters.php')?'active':'' ?>">👥 Voters</a>
    <a href="results.php" class="<?= ($current_page=='results.php')?'active':'' ?>">📊 Results</a>
    <a href="profile.php" class="<?= ($current_page=='profile.php')?'active':'' ?>">👤 Profile</a>
<?php } else { ?>
    <a href="dashboard.php" class="<?= ($current_page=='dashboard.php')?'active':'' ?>">🏠 Dashboard</a>
    <a href="vote.php" class="<?= ($current_page=='vote.php')?'active':'' ?>">🗳️ Vote</a>
    <a href="profile.php" class="<?= ($current_page=='profile.php')?'active':'' ?>">👤 Profile</a>
<?php } ?>
</div>

