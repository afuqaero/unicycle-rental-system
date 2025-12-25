<?php
// rental-summary.php
session_start();
require_once "config.php";
require_once "guard_active_rental.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: login.php');
  exit;
}
$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'] ?? 'User';

// Get user initials for avatar
$nameParts = explode(' ', $student_name);
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (isset($nameParts[1])) {
  $initials .= strtoupper(substr($nameParts[1], 0, 1));
}

// Penalty rules
$GRACE_MINUTES = 10;
$RATE_FIRST_2H = 5.00;
$RATE_AFTER_2H = 10.00;

// totals
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=?");
$stmt->execute([$student_id]);
$totalRentals = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=? AND status IN ('completed','late')");
$stmt->execute([$student_id]);
$completed = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=? AND status='active'");
$stmt->execute([$student_id]);
$activeCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
  SELECT COALESCE(SUM(p.amount),0)
  FROM penalties p
  JOIN rentals r ON r.rental_id = p.rental_id
  WHERE r.student_id=? AND p.status='unpaid'
");
$stmt->execute([$student_id]);
$totalPenalties = (float) $stmt->fetchColumn();

// active rental (if any)
$stmt = $pdo->prepare("
  SELECT r.rental_id, r.start_time, r.expected_return_time, b.bike_name, b.bike_type,
         TIMESTAMPDIFF(MINUTE, r.start_time, r.expected_return_time) AS planned_minutes
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.student_id=? AND r.status='active'
  ORDER BY r.start_time DESC
  LIMIT 1
");
$stmt->execute([$student_id]);
$active = $stmt->fetch();

// history
$stmt = $pdo->prepare("
  SELECT r.rental_id, r.start_time, r.return_time,
         TIMESTAMPDIFF(MINUTE, r.start_time, COALESCE(r.return_time, NOW())) AS duration_minutes,
         r.status, b.bike_name, b.bike_type,
         COALESCE(p.amount,0) AS penalty_amount,
         COALESCE(p.minutes_late,0) AS penalty_minutes_late,
         COALESCE(p.status,'-') AS penalty_status
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  LEFT JOIN penalties p ON p.rental_id = r.rental_id
  WHERE r.student_id=? AND r.status IN ('completed','late')
  ORDER BY r.start_time DESC
");
$stmt->execute([$student_id]);
$history = $stmt->fetchAll();

// Current date
$currentDate = date('l, F j, Y');

function penalty_breakdown(int $lateMinutesAfterGrace, float $rateFirst2h, float $rateAfter2h): string
{
  if ($lateMinutesAfterGrace <= 0)
    return "Within grace period";
  $lateHours = (int) ceil($lateMinutesAfterGrace / 60);
  if ($lateHours <= 2) {
    return "{$lateHours}h × RM" . number_format($rateFirst2h, 2);
  }
  return "2h × RM" . number_format($rateFirst2h, 2) . " + " . ($lateHours - 2) . "h × RM" . number_format($rateAfter2h, 2);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Rental Summary - UniCycle</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="dashboard.css?v=7">
  <style>
    /* Rental Summary Page Specific Styles */
    .summary-stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .summary-stat {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .summary-stat .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 16px;
    }

    .summary-stat .stat-icon.blue {
      background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    }

    .summary-stat .stat-icon.green {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    }

    .summary-stat .stat-icon.orange {
      background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
    }

    .summary-stat .stat-icon.red {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    }

    .summary-stat .stat-value {
      font-size: 28px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 4px;
    }

    .summary-stat .stat-label {
      font-size: 14px;
      color: #64748b;
    }

    /* Penalty Alert */
    .penalty-alert {
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
      border: 1px solid #fecaca;
      border-radius: 16px;
      padding: 20px 24px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .penalty-alert .alert-icon {
      width: 48px;
      height: 48px;
      background: #ef4444;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      flex-shrink: 0;
    }

    .penalty-alert .alert-content {
      flex: 1;
    }

    .penalty-alert h4 {
      color: #dc2626;
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .penalty-alert p {
      color: #7f1d1d;
      font-size: 14px;
    }

    .penalty-alert .pay-btn {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
      padding: 12px 24px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      transition: transform 0.2s;
    }

    .penalty-alert .pay-btn:hover {
      transform: scale(1.05);
    }

    /* Rules Card */
    .rules-card {
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      border: 1px solid #bae6fd;
      border-radius: 16px;
      padding: 20px 24px;
      margin-bottom: 24px;
    }

    .rules-card h4 {
      color: #0369a1;
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .rules-list {
      display: flex;
      gap: 24px;
      flex-wrap: wrap;
    }

    .rule-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: #0c4a6e;
    }

    .rule-item .rule-badge {
      background: #0ea5e9;
      color: white;
      padding: 4px 10px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 13px;
    }

    /* Section Header */
    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
    }

    .section-header h3 {
      font-size: 18px;
      font-weight: 600;
      color: #1e293b;
    }

    /* Active Rental Card */
    .active-rental-card {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      border-radius: 20px;
      padding: 24px;
      margin-bottom: 24px;
      color: white;
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .active-rental-card .rental-icon {
      width: 64px;
      height: 64px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
    }

    .active-rental-card .rental-info {
      flex: 1;
    }

    .active-rental-card .rental-info h4 {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .active-rental-card .rental-info p {
      opacity: 0.9;
      font-size: 14px;
      margin-bottom: 4px;
    }

    .active-rental-card .view-btn {
      background: white;
      color: #059669;
      padding: 12px 24px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      transition: transform 0.2s;
    }

    .active-rental-card .view-btn:hover {
      transform: scale(1.05);
    }

    /* No Active Rental */
    .no-rental-card {
      background: white;
      border-radius: 20px;
      padding: 32px;
      margin-bottom: 24px;
      text-align: center;
      border: 2px dashed #e2e8f0;
    }

    .no-rental-card .no-rental-icon {
      font-size: 48px;
      margin-bottom: 12px;
    }

    .no-rental-card h4 {
      font-size: 18px;
      color: #64748b;
      margin-bottom: 8px;
    }

    .no-rental-card p {
      color: #94a3b8;
      margin-bottom: 16px;
    }

    .no-rental-card .rent-btn {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      color: white;
      padding: 12px 24px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
    }

    /* History Table */
    .history-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .history-card .card-header {
      padding: 20px 24px;
      border-bottom: 1px solid #f1f5f9;
    }

    .history-table {
      width: 100%;
      border-collapse: collapse;
    }

    .history-table th {
      background: #f8fafc;
      padding: 14px 20px;
      text-align: left;
      font-size: 13px;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .history-table td {
      padding: 16px 20px;
      border-bottom: 1px solid #f1f5f9;
      font-size: 14px;
      color: #1e293b;
    }

    .history-table tr:last-child td {
      border-bottom: none;
    }

    .history-table tr:hover {
      background: #f8fafc;
    }

    .bike-cell {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .bike-cell .bike-icon {
      width: 40px;
      height: 40px;
      background: #f1f5f9;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .status-badge.on-time {
      background: #d1fae5;
      color: #059669;
    }

    .status-badge.late {
      background: #fee2e2;
      color: #dc2626;
    }

    .penalty-info {
      font-size: 12px;
      color: #64748b;
      margin-top: 4px;
    }

    .empty-history {
      text-align: center;
      padding: 48px;
    }

    .empty-history .empty-icon {
      font-size: 48px;
      margin-bottom: 12px;
    }

    .empty-history h4 {
      color: #64748b;
      margin-bottom: 8px;
    }

    .empty-history p {
      color: #94a3b8;
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .summary-stats {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .summary-stats {
        grid-template-columns: 1fr;
      }

      .active-rental-card {
        flex-direction: column;
        text-align: center;
      }

      .history-table {
        display: block;
        overflow-x: auto;
      }
    }
  </style>
</head>

<body>

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <a href="dashboard.php" class="sidebar-logo">
        <div class="logo-icon">U</div>
        <span class="logo-text">UniCycle</span>
      </a>
    </div>

    <div class="user-section">
      <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
      <div class="user-welcome">
        <span class="welcome-label">Welcome back,</span>
        <span class="user-name"><?= htmlspecialchars($student_name) ?></span>
      </div>
      <div class="user-stats">
        <div class="user-stat">
          <span class="stat-number"><?= $totalRentals ?></span>
          <span class="stat-text">Total Rides</span>
        </div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item">
        <span class="nav-icon">📊</span>
        <span>Dashboard</span>
      </a>
      <a href="available-bikes.php" class="nav-item">
        <span class="nav-icon">🚲</span>
        <span>Available Bikes</span>
      </a>
      <a href="rental-summary.php" class="nav-item active">
        <span class="nav-icon">📋</span>
        <span>Rental Summary</span>
      </a>
      <a href="complaints.php" class="nav-item">
        <span class="nav-icon">💬</span>
        <span>Complaints</span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <button class="logout-btn" onclick="showLogoutModal()">
        <span>Sign out</span>
      </button>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Header Banner -->
    <div class="header-banner">
      <div class="banner-pattern"></div>
      <div class="banner-content">
        <div class="banner-dots">
          <span></span><span></span><span></span><span></span>
        </div>
        <h1>Rental Summary</h1>
        <p class="banner-date"><?= $currentDate ?></p>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard-content" style="display: block;">

      <!-- Stats -->
      <div class="summary-stats">
        <div class="summary-stat">
          <div class="stat-icon blue">📊</div>
          <div class="stat-value"><?= $totalRentals ?></div>
          <div class="stat-label">Total Rentals</div>
        </div>
        <div class="summary-stat">
          <div class="stat-icon green">✓</div>
          <div class="stat-value"><?= $completed ?></div>
          <div class="stat-label">Completed</div>
        </div>
        <div class="summary-stat">
          <div class="stat-icon orange">🚴</div>
          <div class="stat-value"><?= $activeCount ?></div>
          <div class="stat-label">Active</div>
        </div>
        <div class="summary-stat">
          <div class="stat-icon red">💰</div>
          <div class="stat-value">RM <?= number_format($totalPenalties, 2) ?></div>
          <div class="stat-label">Unpaid Penalties</div>
        </div>
      </div>

      <?php if ($totalPenalties > 0): ?>
        <!-- Penalty Alert -->
        <div class="penalty-alert">
          <div class="alert-icon">⚠️</div>
          <div class="alert-content">
            <h4>You have unpaid penalties</h4>
            <p>Please settle your penalties to continue renting bikes.</p>
          </div>
          <a href="pay-penalty.php" class="pay-btn">Pay RM <?= number_format($totalPenalties, 2) ?></a>
        </div>
      <?php endif; ?>

      <!-- Penalty Rules -->
      <div class="rules-card">
        <h4>📋 Penalty Rules</h4>
        <div class="rules-list">
          <div class="rule-item">
            <span>Grace period:</span>
            <span class="rule-badge"><?= $GRACE_MINUTES ?> min</span>
          </div>
          <div class="rule-item">
            <span>First 2 hours late:</span>
            <span class="rule-badge">RM <?= number_format($RATE_FIRST_2H, 2) ?>/hr</span>
          </div>
          <div class="rule-item">
            <span>After 2 hours:</span>
            <span class="rule-badge">RM <?= number_format($RATE_AFTER_2H, 2) ?>/hr</span>
          </div>
        </div>
      </div>

      <!-- Active Rental Section -->
      <div class="section-header">
        <h3>Active Rental</h3>
      </div>

      <?php if ($active): ?>
        <div class="active-rental-card">
          <div class="rental-icon"><?= $active['bike_type'] === 'mountain' ? '🚵' : '🚲' ?></div>
          <div class="rental-info">
            <h4><?= htmlspecialchars($active['bike_name']) ?></h4>
            <p>Started: <?= date('M j, Y \a\t g:i A', strtotime($active['start_time'])) ?></p>
            <p>Due: <?= date('M j, Y \a\t g:i A', strtotime($active['expected_return_time'])) ?></p>
          </div>
          <a href="active-rental.php" class="view-btn">View Details →</a>
        </div>
      <?php else: ?>
        <div class="no-rental-card">
          <div class="no-rental-icon">🚲</div>
          <h4>No Active Rental</h4>
          <p>You don't have any ongoing rentals right now.</p>
          <a href="available-bikes.php" class="rent-btn">Rent a Bike</a>
        </div>
      <?php endif; ?>

      <!-- Rental History -->
      <div class="section-header">
        <h3>Rental History</h3>
      </div>

      <div class="history-card">
        <?php if (empty($history)): ?>
          <div class="empty-history">
            <div class="empty-icon">📭</div>
            <h4>No rental history yet</h4>
            <p>Your completed rentals will appear here.</p>
          </div>
        <?php else: ?>
          <table class="history-table">
            <thead>
              <tr>
                <th>Bike</th>
                <th>Start Time</th>
                <th>Return Time</th>
                <th>Duration</th>
                <th>Penalty</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($history as $row): ?>
                <?php
                $penAmt = (float) $row['penalty_amount'];
                $lateMinAfterGrace = (int) $row['penalty_minutes_late'];
                $breakdown = penalty_breakdown($lateMinAfterGrace, $RATE_FIRST_2H, $RATE_AFTER_2H);
                $bikeIcon = ($row['bike_type'] ?? 'city') === 'mountain' ? '🚵' : '🚲';
                ?>
                <tr>
                  <td>
                    <div class="bike-cell">
                      <div class="bike-icon"><?= $bikeIcon ?></div>
                      <span><?= htmlspecialchars($row['bike_name']) ?></span>
                    </div>
                  </td>
                  <td><?= date('M j, g:i A', strtotime($row['start_time'])) ?></td>
                  <td><?= $row['return_time'] ? date('M j, g:i A', strtotime($row['return_time'])) : '-' ?></td>
                  <td><?= (int) $row['duration_minutes'] ?> min</td>
                  <td>
                    <?php if ($penAmt > 0): ?>
                      <strong>RM <?= number_format($penAmt, 2) ?></strong>
                      <div class="penalty-info"><?= htmlspecialchars($breakdown) ?></div>
                    <?php else: ?>
                      <span style="color: #10b981;">No penalty</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($row['status'] === 'late'): ?>
                      <span class="status-badge late">Late</span>
                    <?php else: ?>
                      <span class="status-badge on-time">On Time</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

    </div>
  </main>

  <!-- Logout Modal -->
  <div class="modal-overlay" id="logoutModal">
    <div class="modal-box">
      <div class="modal-icon">⚠️</div>
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to sign out?</p>
      <div class="modal-actions">
        <button class="modal-btn cancel" onclick="hideLogoutModal()">Cancel</button>
        <button class="modal-btn confirm" onclick="confirmLogout()">Sign out</button>
      </div>
    </div>
  </div>

  <script>
    function showLogoutModal() {
      document.getElementById('logoutModal').classList.add('active');
    }

    function hideLogoutModal() {
      document.getElementById('logoutModal').classList.remove('active');
    }

    function confirmLogout() {
      window.location.href = 'logout.php';
    }

    document.getElementById('logoutModal').addEventListener('click', function (e) {
      if (e.target === this) hideLogoutModal();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') hideLogoutModal();
    });
  </script>

</body>

</html>