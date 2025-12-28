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

// Get profile picture
$stmt = $pdo->prepare("SELECT profile_pic FROM students WHERE student_id=?");
$stmt->execute([$student_id]);
$profile_pic = $stmt->fetchColumn() ?: null;

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
    <link rel="stylesheet" href="dashboard.css?v=9">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Complaints Page Specific Styles */
        .complaints-header {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-bottom: 24px;
        }

        .complaints-header h2 {
            display: none;
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
    <style>
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 12px 16px;
                background: linear-gradient(135deg, #1a365d 0%, #2563eb 100%);
                position: sticky;
                top: 0;
                z-index: 100;
            }

            .mobile-header-left {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .mobile-back-btn {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.15);
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mobile-title {
                color: white;
                font-size: 18px;
                font-weight: 600;
            }

            .mobile-menu-btn {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.15);
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
            }

            .header-banner {
                display: none;
            }

            .dashboard-content {
                padding-top: 30px;
            }

            .bottom-nav {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
                z-index: 100;
                padding: 8px 0;
                padding-bottom: max(8px, env(safe-area-inset-bottom));
            }

            .bottom-nav-item {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 4px;
                padding: 8px;
                text-decoration: none;
                color: #64748b;
                font-size: 11px;
                font-weight: 500;
            }

            .bottom-nav-item.active {
                color: #2563eb;
            }

            .bottom-nav-item i {
                font-size: 20px;
            }

            .main-content {
                padding-bottom: 80px;
            }

            .mobile-menu-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 200;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s;
            }

            .mobile-menu-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            .mobile-menu-panel {
                position: fixed;
                top: 0;
                right: -280px;
                width: 280px;
                height: 100%;
                background: white;
                z-index: 201;
                transition: right 0.3s ease;
                display: flex;
                flex-direction: column;
            }

            .mobile-menu-overlay.active .mobile-menu-panel {
                right: 0;
            }

            .mobile-menu-header {
                padding: 20px;
                background: linear-gradient(135deg, #1a365d 0%, #2563eb 100%);
                color: white;
                position: relative;
            }

            .mobile-menu-close {
                position: absolute;
                top: 12px;
                right: 12px;
                width: 32px;
                height: 32px;
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.15);
                border: none;
                color: white;
                font-size: 16px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mobile-menu-close:hover {
                background: rgba(255, 255, 255, 0.25);
            }

            .mobile-menu-user {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .mobile-menu-avatar {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.2);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                font-weight: 600;
                overflow: hidden;
            }

            .mobile-menu-name {
                font-size: 16px;
                font-weight: 600;
            }

            .mobile-menu-email {
                font-size: 12px;
                opacity: 0.8;
            }

            .mobile-menu-nav {
                flex: 1;
                padding: 16px 0;
            }

            .mobile-menu-item {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 14px 20px;
                color: #1e293b;
                text-decoration: none;
                font-size: 15px;
                font-weight: 500;
            }

            .mobile-menu-item:hover {
                background: #f8fafc;
            }

            .mobile-menu-item.active {
                color: #2563eb;
                background: #eff6ff;
            }

            .mobile-menu-item i {
                width: 24px;
                font-size: 18px;
            }

            .mobile-menu-footer {
                padding: 16px 20px;
                border-top: 1px solid #e2e8f0;
            }

            .mobile-logout-btn {
                width: 100%;
                padding: 12px;
                background: #fee2e2;
                color: #dc2626;
                border: none;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
        }

        @media (min-width: 769px) {

            .mobile-header,
            .bottom-nav,
            .mobile-menu-overlay {
                display: none !important;
            }
        }
    </style>

    <!-- Mobile Header -->
    <header class="mobile-header">
        <div class="mobile-header-left">
            <span class="mobile-title">Complaints</span>
        </div>
        <button class="mobile-menu-btn" onclick="openMobileMenu()"><i class="fas fa-bars"></i></button>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <div class="logo-icon">U</div>
                <span class="logo-text">UniCycle</span>
            </a>
        </div>

        <div class="user-section">
            <div class="user-avatar">
                <?php if ($profile_pic && file_exists('assets/uploads/' . $profile_pic)): ?>
                    <img src="assets/uploads/<?= htmlspecialchars($profile_pic) ?>" alt="Profile"
                        style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                    <?= htmlspecialchars($initials) ?>
                <?php endif; ?>
            </div>
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
                <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="available-bikes.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-bicycle"></i></span>
                <span>Available Bikes</span>
            </a>
            <a href="rental-summary.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-clock-rotate-left"></i></span>
                <span>Rental Summary</span>
            </a>
            <a href="complaints.php" class="nav-item active">
                <span class="nav-icon"><i class="fas fa-comment-dots"></i></span>
                <span>Complaints</span>
            </a>
            <a href="settings.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-cog"></i></span>
                <span>Settings</span>
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
                    <span><i class="fas fa-plus"></i></span> Lodge Complaint
                </button>
            </div>

            <?php if ($success): ?>
                <div class="success-message">
                    <span><i class="fas fa-check-circle"></i></span> Your complaint has been submitted successfully!
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-message">
                    <span><i class="fas fa-times-circle"></i></span> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="complaints-stats">
                <div class="complaint-stat">
                    <div class="stat-icon total"><i class="fas fa-file-lines"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalComplaints ?></div>
                        <div class="stat-label">Total Complaints</div>
                    </div>
                </div>
                <div class="complaint-stat">
                    <div class="stat-icon open"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $openComplaints ?></div>
                        <div class="stat-label">Open</div>
                    </div>
                </div>
                <div class="complaint-stat">
                    <div class="stat-icon resolved"><i class="fas fa-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $resolvedComplaints ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                </div>
            </div>

            <!-- Complaints List -->
            <?php if (empty($complaints)): ?>
                <div class="empty-complaints">
                    <div class="empty-icon"><i class="fas fa-comment-dots"></i></div>
                    <h4>No complaints yet</h4>
                    <p>You haven't submitted any complaints. Lodge one if you encounter any issues.</p>
                    <button class="new-complaint-btn" onclick="openComplaintModal()">
                        <span><i class="fas fa-plus"></i></span> Lodge Complaint
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
                                        <span><i class="fas fa-bicycle"></i>
                                            <?= htmlspecialchars($c['bike_name'] ?? 'General') ?></span>
                                        <span><i class="fas fa-calendar"></i>
                                            <?= date('M j, Y \a\t g:i A', strtotime($c['created_at'])) ?></span>
                                    </div>
                                </div>
                                <span class="complaint-status <?= $c['status'] ?>">
                                    <?= $c['status'] === 'resolved' ? '<i class="fas fa-check"></i> Resolved' : '<i class="fas fa-hourglass-half"></i> Open' ?>
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

    <!-- Mobile Slide Menu -->
    <div class="mobile-menu-overlay" id="mobileMenu" onclick="closeMobileMenu(event)">
        <div class="mobile-menu-panel" onclick="event.stopPropagation()">
            <div class="mobile-menu-header">
                <button class="mobile-menu-close" onclick="closeMobileMenu()"><i class="fas fa-times"></i></button>
                <div class="mobile-menu-user">
                    <div class="mobile-menu-avatar">
                        <?php if ($profile_pic && file_exists('assets/uploads/' . $profile_pic)): ?>
                            <img src="assets/uploads/<?= htmlspecialchars($profile_pic) ?>" alt="Profile"
                                style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?= htmlspecialchars($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="mobile-menu-name"><?= htmlspecialchars($student_name) ?></div>
                        <div class="mobile-menu-email"><?= $totalRentals ?> total rides</div>
                    </div>
                </div>
            </div>
            <nav class="mobile-menu-nav">
                <a href="dashboard.php" class="mobile-menu-item"><i class="fas fa-gauge-high"></i> Dashboard</a>
                <a href="available-bikes.php" class="mobile-menu-item"><i class="fas fa-bicycle"></i> Available
                    Bikes</a>
                <a href="rental-summary.php" class="mobile-menu-item"><i class="fas fa-clock-rotate-left"></i> Rental
                    Summary</a>
                <a href="complaints.php" class="mobile-menu-item active"><i class="fas fa-comment-dots"></i>
                    Complaints</a>
                <a href="settings.php" class="mobile-menu-item"><i class="fas fa-cog"></i> Settings</a>
            </nav>
            <div class="mobile-menu-footer">
                <button class="mobile-logout-btn" onclick="showLogoutModal()"><i class="fas fa-sign-out-alt"></i> Sign
                    Out</button>
            </div>
        </div>
    </div>

    <!-- Complaint Modal -->
    <div class="complaint-modal" id="complaintModal">
        <div class="complaint-modal-box">
            <h3><i class="fas fa-comment-dots"></i> Lodge a Complaint</h3>
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
            <div class="modal-icon"><i class="fas fa-exclamation-circle"></i></div>
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
        function showLogoutModal() { document.getElementById('logoutModal').classList.add('active'); }
        function hideLogoutModal() { document.getElementById('logoutModal').classList.remove('active'); }
        function confirmLogout() { window.location.href = 'logout.php'; }
        document.getElementById('logoutModal').addEventListener('click', function (e) {
            if (e.target === this) hideLogoutModal();
        });

        // Mobile Menu
        function openMobileMenu() {
            document.getElementById('mobileMenu').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeMobileMenu(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('mobileMenu').classList.remove('active');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeComplaintModal();
                hideLogoutModal();
                closeMobileMenu();
            }
        });
    </script>

</body>

</html>