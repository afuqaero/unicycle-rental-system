<?php
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

// Get total rides count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=?");
$stmt->execute([$student_id]);
$totalRentals = (int) $stmt->fetchColumn();

// submit complaint
$error = "";
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = isset($_POST['rental_id']) ? (int) $_POST['rental_id'] : 0;
    $message = trim($_POST['message'] ?? "");

    if ($message === "") {
        $error = "Message is required.";
    } else {
        $code = "COMP" . str_pad((string) random_int(1, 999999), 6, "0", STR_PAD_LEFT);

        $stmt = $pdo->prepare("
          INSERT INTO complaints (complaint_code, student_id, rental_id, message, status)
          VALUES (?, ?, ?, ?, 'open')
        ");
        $stmt->execute([$code, $student_id, $rental_id > 0 ? $rental_id : null, $message]);
        $success = true;
    }
}

// rentals list for dropdown (completed/late only)
$stmt = $pdo->prepare("
  SELECT r.rental_id, r.start_time, b.bike_name
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.student_id=? AND r.status IN ('completed','late')
  ORDER BY r.start_time DESC
");
$stmt->execute([$student_id]);
$rentals = $stmt->fetchAll();

// complaints list
$stmt = $pdo->prepare("
  SELECT c.complaint_code, c.created_at, c.message, c.status,
         r.rental_code, b.bike_name
  FROM complaints c
  LEFT JOIN rentals r ON r.rental_id = c.rental_id
  LEFT JOIN bikes b ON b.bike_id = r.bike_id
  WHERE c.student_id=?
  ORDER BY c.created_at DESC
");
$stmt->execute([$student_id]);
$complaints = $stmt->fetchAll();

// Count stats
$totalComplaints = count($complaints);
$openComplaints = count(array_filter($complaints, fn($c) => $c['status'] === 'open'));
$resolvedComplaints = $totalComplaints - $openComplaints;

// Current date
$currentDate = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Complaints - UniCycle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css?v=7">
    <style>
        /* Complaints Page Specific Styles */
        .complaints-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .complaints-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
        }

        .new-complaint-btn {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .new-complaint-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3);
        }

        /* Stats Row */
        .complaints-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .complaint-stat {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .complaint-stat .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .complaint-stat .stat-icon.total {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        }

        .complaint-stat .stat-icon.open {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }

        .complaint-stat .stat-icon.resolved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }

        .complaint-stat .stat-info {
            flex: 1;
        }

        .complaint-stat .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }

        .complaint-stat .stat-label {
            font-size: 14px;
            color: #64748b;
        }

        /* Success Message */
        .success-message {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 1px solid #6ee7b7;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #065f46;
            font-weight: 500;
        }

        /* Error Message */
        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 1px solid #fca5a5;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #991b1b;
            font-weight: 500;
        }

        /* Complaints List */
        .complaints-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .complaint-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .complaint-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .complaint-card-header {
            padding: 20px 24px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 1px solid #f1f5f9;
        }

        .complaint-card-header .complaint-info h4 {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .complaint-card-header .complaint-meta {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: #64748b;
        }

        .complaint-card-header .complaint-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .complaint-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .complaint-status.open {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .complaint-status.resolved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .complaint-card-body {
            padding: 20px 24px;
            background: #f8fafc;
        }

        .complaint-card-body p {
            color: #475569;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        /* Empty State */
        .empty-complaints {
            background: white;
            border-radius: 20px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .empty-complaints .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-complaints h4 {
            font-size: 20px;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .empty-complaints p {
            color: #64748b;
            margin-bottom: 20px;
        }

        /* Modal Overlay */
        .complaint-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 1000;
        }

        .complaint-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .complaint-modal-box {
            background: white;
            border-radius: 24px;
            width: 100%;
            max-width: 500px;
            padding: 32px;
            transform: scale(0.9);
            transition: transform 0.3s;
        }

        .complaint-modal.active .complaint-modal-box {
            transform: scale(1);
        }

        .complaint-modal-box h3 {
            font-size: 22px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: border-color 0.2s;
            font-family: inherit;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 14px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-buttons .cancel-btn {
            background: #f1f5f9;
            border: none;
            color: #64748b;
        }

        .modal-buttons .cancel-btn:hover {
            background: #e2e8f0;
        }

        .modal-buttons .submit-btn {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            color: white;
        }

        .modal-buttons .submit-btn:hover {
            transform: scale(1.02);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .complaints-stats {
                grid-template-columns: 1fr;
            }

            .complaints-header {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
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
            <a href="rental-summary.php" class="nav-item">
                <span class="nav-icon">📋</span>
                <span>Rental Summary</span>
            </a>
            <a href="complaints.php" class="nav-item active">
                <span class="nav-icon">💬</span>
                <span>Complaints</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <button class="logout-btn" onclick="showLogoutModal()">
                <span>🚪</span>
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
                <h1>Complaints</h1>
                <p class="banner-date"><?= $currentDate ?></p>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content" style="display: block;">

            <!-- Header with New Complaint Button -->
            <div class="complaints-header">
                <h2>Your Complaints</h2>
                <button class="new-complaint-btn" onclick="openComplaintModal()">
                    <span>💬</span> Lodge Complaint
                </button>
            </div>

            <?php if ($success): ?>
                <div class="success-message">
                    <span>✅</span> Your complaint has been submitted successfully!
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-message">
                    <span>❌</span> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="complaints-stats">
                <div class="complaint-stat">
                    <div class="stat-icon total">📝</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalComplaints ?></div>
                        <div class="stat-label">Total Complaints</div>
                    </div>
                </div>
                <div class="complaint-stat">
                    <div class="stat-icon open">⏳</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $openComplaints ?></div>
                        <div class="stat-label">Open</div>
                    </div>
                </div>
                <div class="complaint-stat">
                    <div class="stat-icon resolved">✓</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $resolvedComplaints ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                </div>
            </div>

            <!-- Complaints List -->
            <?php if (empty($complaints)): ?>
                <div class="empty-complaints">
                    <div class="empty-icon">💬</div>
                    <h4>No complaints yet</h4>
                    <p>You haven't submitted any complaints. Lodge one if you encounter any issues.</p>
                    <button class="new-complaint-btn" onclick="openComplaintModal()">
                        <span>💬</span> Lodge Complaint
                    </button>
                </div>
            <?php else: ?>
                <div class="complaints-list">
                    <?php foreach ($complaints as $c): ?>
                        <div class="complaint-card">
                            <div class="complaint-card-header">
                                <div class="complaint-info">
                                    <h4>Complaint #<?= htmlspecialchars($c['complaint_code']) ?></h4>
                                    <div class="complaint-meta">
                                        <span>🚲 <?= htmlspecialchars($c['bike_name'] ?? 'General') ?></span>
                                        <span>📅 <?= date('M j, Y \a\t g:i A', strtotime($c['created_at'])) ?></span>
                                    </div>
                                </div>
                                <span class="complaint-status <?= $c['status'] ?>">
                                    <?= $c['status'] === 'resolved' ? '✓ Resolved' : '⏳ Open' ?>
                                </span>
                            </div>
                            <div class="complaint-card-body">
                                <p><?= nl2br(htmlspecialchars($c['message'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Complaint Modal -->
    <div class="complaint-modal" id="complaintModal">
        <div class="complaint-modal-box">
            <h3>💬 Lodge a Complaint</h3>
            <form method="post" action="complaints.php">
                <div class="form-group">
                    <label>Select Rental (Optional)</label>
                    <select name="rental_id">
                        <option value="0">General complaint...</option>
                        <?php foreach ($rentals as $r): ?>
                            <option value="<?= (int) $r['rental_id'] ?>">
                                <?= htmlspecialchars($r['bike_name']) ?> -
                                <?= date('M j, Y', strtotime($r['start_time'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Your Message</label>
                    <textarea name="message" placeholder="Describe your issue in detail..." required></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeComplaintModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Submit Complaint</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-box">
            <div class="modal-icon">🚪</div>
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to sign out?</p>
            <div class="modal-actions">
                <button class="modal-btn cancel" onclick="hideLogoutModal()">Cancel</button>
                <button class="modal-btn confirm" onclick="confirmLogout()">Sign out</button>
            </div>
        </div>
    </div>

    <script>
        // Complaint Modal
        function openComplaintModal() {
            document.getElementById('complaintModal').classList.add('active');
        }

        function closeComplaintModal() {
            document.getElementById('complaintModal').classList.remove('active');
        }

        document.getElementById('complaintModal').addEventListener('click', function (e) {
            if (e.target === this) closeComplaintModal();
        });

        // Logout Modal
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
            if (e.key === 'Escape') {
                closeComplaintModal();
                hideLogoutModal();
            }
        });
    </script>

</body>

</html>