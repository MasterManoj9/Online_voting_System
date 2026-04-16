<?php
session_start();
include "config.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

// Check election status
$settings = $conn->query("SELECT election_status FROM settings WHERE id=1")->fetch_assoc();
$election_active = ($settings['election_status'] === 'ON');

// Only fetch results data if election is OFF
$data = [];
$winner_id = null;
$max_votes = 1;
$total_votes = 0;
$chartLabels = [];
$chartVotes = [];
$winner_name = '';
$winner_party = '';
$winner_votes = 0;
$winner_image = '';

if (!$election_active) {
    $sql = "SELECT c.*, COUNT(v.id) as votes 
            FROM candidates c
            LEFT JOIN votes v ON c.id=v.candidate_id
            GROUP BY c.id
            ORDER BY votes DESC";

    $result = $conn->query($sql);

    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    if (!empty($data) && $data[0]['votes'] > 0) {
        $winner_id = $data[0]['id'];
        $winner_name = $data[0]['name'];
        $winner_party = $data[0]['party'];
        $winner_votes = $data[0]['votes'];
        $winner_image = $data[0]['image'] ?? '';
    }

    $max_votes = (!empty($data)) ? max(array_column($data, 'votes')) : 1;
    if($max_votes == 0) $max_votes = 1;

    $total_votes = array_sum(array_column($data, 'votes'));

    $chartColors = [
        'rgba(79, 70, 229, 0.85)',
        'rgba(16, 185, 129, 0.85)',
        'rgba(245, 158, 11, 0.85)',
        'rgba(239, 68, 68, 0.85)',
        'rgba(139, 92, 246, 0.85)',
        'rgba(6, 182, 212, 0.85)',
        'rgba(236, 72, 153, 0.85)',
        'rgba(34, 197, 94, 0.85)',
    ];
    $chartBorderColors = [
        'rgba(79, 70, 229, 1)',
        'rgba(16, 185, 129, 1)',
        'rgba(245, 158, 11, 1)',
        'rgba(239, 68, 68, 1)',
        'rgba(139, 92, 246, 1)',
        'rgba(6, 182, 212, 1)',
        'rgba(236, 72, 153, 1)',
        'rgba(34, 197, 94, 1)',
    ];

    foreach ($data as $i => $row) {
        $chartLabels[] = $row['name'] . ' (' . $row['party'] . ')';
        $chartVotes[] = (int)$row['votes'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results</title>
    <link rel="stylesheet" href="style.css">
    <?php if (!$election_active) { ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php } ?>
    <style>
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 30px;
        }
        .chart-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
        }
        .chart-card h3 {
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: var(--dark);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .total-votes-banner {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--primary), #8B5CF6);
            color: white;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        @media (max-width: 768px) {
            .charts-grid { grid-template-columns: 1fr; }
        }

        /* ── Election Active Lockscreen ── */
        .results-locked {
            text-align: center;
            padding: 80px 30px;
            animation: slideUp 0.6s ease-out forwards;
        }
        .results-locked .lock-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            display: block;
            animation: pulse 2s infinite;
        }
        .results-locked h2 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 12px;
        }
        .results-locked p {
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.7;
        }
        .live-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #10B981;
            border-radius: 50%;
            margin-right: 6px;
            animation: blink 1.2s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* ── Winner Popup Modal ── */
        .winner-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            animation: fadeInOverlay 0.3s ease-out forwards;
        }
        .winner-overlay.show { display: flex; }
        @keyframes fadeInOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .winner-modal {
            background: white;
            border-radius: 24px;
            max-width: 420px;
            width: 90%;
            padding: 40px 35px 30px;
            text-align: center;
            position: relative;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.7) translateY(30px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .winner-modal .trophy {
            font-size: 4rem;
            display: block;
            margin-bottom: 10px;
            animation: bounce 0.8s ease-out;
        }
        @keyframes bounce {
            0% { transform: translateY(-40px); opacity: 0; }
            50% { transform: translateY(8px); }
            100% { transform: translateY(0); opacity: 1; }
        }

        .winner-modal .confetti-text {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .winner-modal h2 {
            font-size: 1.7rem;
            background: linear-gradient(135deg, #F59E0B, #F97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px;
        }

        .winner-modal .party-name {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .winner-modal .winner-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #F59E0B;
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
            margin-bottom: 15px;
        }

        .winner-modal .votes-pill {
            display: inline-block;
            background: linear-gradient(135deg, #059669, #10B981);
            color: white;
            padding: 8px 22px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .winner-modal .close-btn {
            width: 100%;
            padding: 14px;
            background: rgba(79, 70, 229, 0.08);
            color: var(--primary);
            border: 1px solid rgba(79, 70, 229, 0.2);
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            font-family: inherit;
            transition: var(--transition);
        }
        .winner-modal .close-btn:hover {
            background: rgba(79, 70, 229, 0.15);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container">

<?php if ($election_active) { ?>
    <!-- ════════════ ELECTION ACTIVE → RESULTS LOCKED ════════════ -->
    <div class="section results-locked">
        <span class="lock-icon">🔒</span>
        <h2>Results Are Locked</h2>
        <p>
            <span class="live-dot"></span><strong>Election is currently active.</strong><br><br>
            The results will be shown after the completion of the election.
            Please wait for the administrator to stop the election to view the final results.
        </p>
    </div>

<?php } else { ?>
    <!-- ════════════ ELECTION ENDED → SHOW RESULTS ════════════ -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-top: 20px;">
        <div>
            <h2 class="title" style="margin-bottom: 5px;">Election Results &nbsp;📊</h2>
            <p style="color: var(--text-muted);">Final vote tallies for all candidates.</p>
        </div>
        <div class="total-votes-banner">
            🗳️ Total Votes: <?= $total_votes ?>
        </div>
    </div>

    <!-- Charts Section -->
    <?php if (!empty($data) && $total_votes > 0) { ?>
    <div class="charts-grid">
        <div class="chart-card">
            <h3>📊 Vote Distribution (Bar Chart)</h3>
            <div class="chart-container">
                <canvas id="barChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3>🥧 Vote Share (Pie Chart)</h3>
            <div class="chart-container">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- Candidate Results List -->
    <div style="max-width: 800px; margin-top: 30px;">
    <?php foreach($data as $index => $row) { 
        $is_winner = ($row['id'] == $winner_id);
        $percent = round(($row['votes'] / $max_votes) * 100);
        $vote_share = ($total_votes > 0) ? round(($row['votes'] / $total_votes) * 100, 1) : 0;
    ?>
        <div class="result-card <?= $is_winner ? 'winner' : '' ?>" style="animation-delay: <?= $index * 0.1 ?>s;">
            <?php if (!empty($row['image'])) { ?>
                <img src="<?= htmlspecialchars($row['image']) ?>" alt="Candidate">
            <?php } ?>
            <div style="flex: 1;">
                <h3 style="margin-bottom: 5px;"><?= htmlspecialchars($row['name']) ?></h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;"><?= htmlspecialchars($row['party']) ?></p>
                
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="result-bar-bg">
                        <div class="result-bar-fill" style="width: <?= $percent ?>%;"></div>
                    </div>
                    <div style="font-weight: 700; color: var(--dark); min-width: 90px;">
                        <?= $row['votes'] ?> Votes (<?= $vote_share ?>%)
                    </div>
                </div>
            </div>
            <?php if ($is_winner) { ?>
                <div class="winner-badge">🏆 Winner</div>
            <?php } ?>
        </div>
    <?php } ?>
    <?php if (empty($data)) { ?>
        <p>No candidates available.</p>
    <?php } ?>
    </div>

    <!-- ════════════ WINNER POPUP MODAL ════════════ -->
    <?php if ($winner_id && $total_votes > 0) { ?>
    <div class="winner-overlay" id="winnerPopup">
        <div class="winner-modal">
            <span class="trophy">🏆</span>
            <p class="confetti-text">🎉 Election Winner 🎉</p>
            <?php if (!empty($winner_image)) { ?>
                <img src="<?= htmlspecialchars($winner_image) ?>" alt="Winner" class="winner-avatar">
            <?php } ?>
            <h2><?= htmlspecialchars($winner_name) ?></h2>
            <p class="party-name"><?= htmlspecialchars($winner_party) ?></p>
            <div class="votes-pill">🗳️ <?= $winner_votes ?> Votes (<?= ($total_votes > 0) ? round(($winner_votes / $total_votes) * 100, 1) : 0 ?>%)</div>
            <br><br>
            <button type="button" class="close-btn" onclick="closeWinnerPopup()">👍 View Detailed Results</button>
        </div>
    </div>
    <?php } ?>

<?php } ?>

</div>

<script>
<?php if (!$election_active) { ?>

    // Trigger progress bar animations
    setTimeout(() => {
        document.querySelectorAll('.result-bar-fill').forEach(bar => {
            bar.style.width = bar.style.width;
        });
    }, 100);

    // Chart.js Data
    const labels = <?= json_encode($chartLabels) ?>;
    const votes = <?= json_encode($chartVotes) ?>;
    const bgColors = <?= json_encode(array_slice($chartColors, 0, count($data))) ?>;
    const borderColors = <?= json_encode(array_slice($chartBorderColors, 0, count($data))) ?>;

    <?php if (!empty($data) && $total_votes > 0) { ?>
    // Bar Chart
    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Votes',
                data: votes,
                backgroundColor: bgColors,
                borderColor: borderColors,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    padding: 12,
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    cornerRadius: 8,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { 
                        stepSize: 1,
                        font: { family: 'Outfit' }
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { 
                        font: { family: 'Outfit', size: 11 },
                        maxRotation: 45,
                    },
                    grid: { display: false }
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeOutBounce'
            }
        }
    });

    // Pie Chart
    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: votes,
                backgroundColor: bgColors,
                borderColor: 'rgba(255,255,255,0.8)',
                borderWidth: 3,
                hoverOffset: 15,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { family: 'Outfit', size: 11 },
                        usePointStyle: true,
                        pointStyleWidth: 10,
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' votes (' + pct + '%)';
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                duration: 1500,
                easing: 'easeOutCirc'
            }
        }
    });
    <?php } ?>

    // ─── Winner Popup: Show after a short delay ───
    <?php if ($winner_id && $total_votes > 0) { ?>
    setTimeout(() => {
        document.getElementById('winnerPopup').classList.add('show');
    }, 800);
    <?php } ?>

    function closeWinnerPopup() {
        const popup = document.getElementById('winnerPopup');
        popup.style.animation = 'fadeOutOverlay 0.3s ease-out forwards';
        popup.querySelector('.winner-modal').style.animation = 'popOut 0.3s ease-in forwards';
        setTimeout(() => { popup.classList.remove('show'); }, 300);
    }

<?php } ?>
</script>

<style>
    @keyframes fadeOutOverlay {
        to { opacity: 0; }
    }
    @keyframes popOut {
        to { opacity: 0; transform: scale(0.7) translateY(30px); }
    }
</style>

</body>
</html>
