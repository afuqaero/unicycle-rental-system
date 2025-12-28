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

// block if unpaid penalties
$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM penalties p
  JOIN rentals r ON r.rental_id = p.rental_id
  WHERE r.student_id=? AND p.status='unpaid'
");
$stmt->execute([$student_id]);
if ((int) $stmt->fetchColumn() > 0) {
    header("Location: pay-penalty.php");
    exit;
}

$bike_id = isset($_GET['bike_id']) ? (int) $_GET['bike_id'] : 0;
if ($bike_id <= 0) {
    header("Location: available-bikes.php");
    exit;
}

$stmt = $pdo->prepare("
  SELECT bike_id, bike_name, bike_type, status, location
  FROM bikes
  WHERE bike_id=?
");
$stmt->execute([$bike_id]);
$bike = $stmt->fetch();
if (!$bike || $bike['status'] !== 'available') {
    header("Location: available-bikes.php");
    exit;
}

$rate = 3.00;
$bikeIcon = ($bike['bike_type'] ?? 'city') === 'mountain' ? '🚵' : '🚲';
$currentDate = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Confirm Rental - UniCycle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css?v=9">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Confirm Rental Styles */
        .confirm-container {
            max-width: 560px;
            margin: 0 auto;
        }

        .confirm-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .confirm-header {
            background: linear-gradient(135deg, #1a6dff 0%, #0052d4 100%);
            padding: 24px;
            text-align: center;
            color: white;
        }

        .confirm-header .bike-icon {
            font-size: 48px;
            margin-bottom: 8px;
        }

        .confirm-header h2 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .confirm-header p {
            opacity: 0.9;
            font-size: 13px;
        }

        .confirm-body {
            padding: 24px;
        }

        /* Bike Details */
        .bike-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row .label {
            font-size: 14px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-row .value {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
        }

        /* Duration Selector */
        .duration-section {
            margin-bottom: 20px;
        }

        .duration-section label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }

        .duration-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .duration-option {
            position: relative;
        }

        .duration-option input {
            position: absolute;
            opacity: 0;
        }

        .duration-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 14px 8px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .duration-option input:checked+label {
            border-color: #1a6dff;
            background: linear-gradient(135deg, #e8f4ff 0%, #dbeafe 100%);
        }

        .duration-option label:hover {
            border-color: #93c5fd;
        }

        .duration-option .hours {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }

        .duration-option .unit {
            font-size: 12px;
            color: #64748b;
        }

        .duration-option .price {
            font-size: 13px;
            font-weight: 600;
            color: #1a6dff;
            margin-top: 4px;
        }

        /* Price Summary */
        .price-summary {
            background: linear-gradient(135deg, #e8f4ff 0%, #dbeafe 100%);
            border: 2px solid #93c5fd;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin-bottom: 20px;
        }

        .price-summary .total-label {
            font-size: 14px;
            color: #0052d4;
            margin-bottom: 4px;
        }

        .price-summary .total-amount {
            font-size: 36px;
            font-weight: 700;
            color: #1a6dff;
        }

        /* Buttons */
        .form-buttons {
            display: flex;
            gap: 12px;
        }

        .form-buttons .back-btn {
            flex: 1;
            padding: 16px;
            background: #f1f5f9;
            color: #64748b;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }

        .form-buttons .back-btn:hover {
            background: #e2e8f0;
        }

        .form-buttons .submit-btn {
            flex: 2;
            padding: 16px;
            background: linear-gradient(135deg, #1a6dff 0%, #0052d4 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .form-buttons .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(26, 109, 255, 0.3);
        }

        @media (max-width: 480px) {
            .duration-options {
                grid-template-columns: repeat(2, 1fr);
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
                <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="available-bikes.php" class="nav-item active">
                <span class="nav-icon"><i class="fas fa-bicycle"></i></span>
                <span>Available Bikes</span>
            </a>
            <a href="rental-summary.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-clock-rotate-left"></i></span>
                <span>Rental Summary</span>
            </a>
            <a href="complaints.php" class="nav-item">
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
                <h1>Confirm Rental</h1>
                <p class="banner-date"><?= $currentDate ?></p>
            </div>
        </div>

        <!-- Content -->
        <div class="dashboard-content" style="display: block;">
            <div class="confirm-container">
                <div class="confirm-card">
                    <div class="confirm-header">
                        <div class="bike-icon"><i
                                class="fas <?= ($bike['bike_type'] ?? 'city') === 'mountain' ? 'fa-mountain' : 'fa-bicycle' ?>"></i>
                        </div>
                        <h2><?= htmlspecialchars($bike['bike_name']) ?></h2>
                        <p>Select duration and confirm</p>
                    </div>

                    <div class="confirm-body">
                        <form action="payment.php" method="post">
                            <input type="hidden" name="bike_id" value="<?= (int) $bike['bike_id'] ?>">

                            <!-- Bike Details -->
                            <div class="bike-details">
                                <div class="detail-row">
                                    <span class="label"><i class="fas fa-location-dot"></i> Location</span>
                                    <span
                                        class="value"><?= htmlspecialchars($bike['location'] ?? 'Main Bike Area') ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label"><i class="fas fa-coins"></i> Rate</span>
                                    <span class="value">RM <?= number_format($rate, 2) ?> / hour</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label"><i class="fas fa-calendar"></i> Date</span>
                                    <span class="value"><?= date("M j, Y g:i A") ?></span>
                                </div>
                            </div>

                            <!-- Duration Selector (1-4 hours only) -->
                            <div class="duration-section">
                                <label>Choose Duration</label>
                                <div class="duration-options">
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                        <div class="duration-option">
                                            <input type="radio" name="hours" id="hours<?= $i ?>" value="<?= $i ?>" <?= $i === 1 ? 'checked' : '' ?>>
                                            <label for="hours<?= $i ?>">
                                                <span class="hours"><?= $i ?></span>
                                                <span class="unit">hour<?= $i > 1 ? 's' : '' ?></span>
                                                <span class="price">RM <?= number_format($rate * $i, 2) ?></span>
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <!-- Price Summary -->
                            <div class="price-summary">
                                <div class="total-label">Total Amount</div>
                                <div class="total-amount" id="totalAmount">RM <?= number_format($rate, 2) ?></div>
                            </div>

                            <!-- Buttons -->
                            <div class="form-buttons">
                                <a href="available-bikes.php" class="back-btn">Cancel</a>
                                <button type="submit" class="submit-btn">Proceed to Payment →</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
        const rate = <?= $rate ?>;

        // Update total when duration changes
        document.querySelectorAll('input[name="hours"]').forEach(input => {
            input.addEventListener('change', function () {
                const hours = parseInt(this.value);
                const total = rate * hours;
                document.getElementById('totalAmount').textContent = 'RM ' + total.toFixed(2);
            });
        });

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