<?php
/**
 * Admin Revenue Management
 * View payment and revenue statistics with beautiful charts
 */
require_once 'includes/auth_check.php';

// Total revenue
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'")->fetchColumn();
$totalPenalties = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM penalties WHERE status = 'paid'")->fetchColumn();

// This month's revenue
$monthRevenue = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) FROM payments 
    WHERE status = 'paid' AND MONTH(paid_at) = MONTH(CURRENT_DATE()) AND YEAR(paid_at) = YEAR(CURRENT_DATE())
")->fetchColumn();

// Last month's revenue for comparison
$lastMonthRevenue = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) FROM payments 
    WHERE status = 'paid' AND MONTH(paid_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
    AND YEAR(paid_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
")->fetchColumn();

// Calculate growth percentage
$growthPercentage = $lastMonthRevenue > 0 ? (($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

// Payment methods breakdown
$methodBreakdown = $pdo->query("
    SELECT method, COUNT(*) as count, SUM(amount) as total
    FROM payments WHERE status = 'paid'
    GROUP BY method
")->fetchAll();

// Recent payments
$payments = $pdo->query("
    SELECT p.*, r.rental_code, s.name as student_name, b.bike_code
    FROM payments p
    JOIN rentals r ON p.rental_id = r.rental_id
    JOIN students s ON r.student_id = s.student_id
    JOIN bikes b ON r.bike_id = b.bike_id
    ORDER BY p.paid_at DESC
    LIMIT 10
")->fetchAll();

// Daily revenue for chart (last 30 days)
$dailyRevenue = $pdo->query("
    SELECT DATE(paid_at) as date, SUM(amount) as total
    FROM payments WHERE status = 'paid' AND paid_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
    GROUP BY DATE(paid_at)
    ORDER BY date
")->fetchAll();

// Monthly revenue for chart (last 6 months)
$monthlyRevenue = $pdo->query("
    SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, 
           DATE_FORMAT(paid_at, '%b %Y') as label,
           SUM(amount) as total
    FROM payments WHERE status = 'paid' AND paid_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(paid_at, '%Y-%m'), DATE_FORMAT(paid_at, '%b %Y')
    ORDER BY month
")->fetchAll();

// Hourly distribution (for peak hours analysis)
$hourlyRevenue = $pdo->query("
    SELECT HOUR(paid_at) as hour, COUNT(*) as transactions, SUM(amount) as total
    FROM payments WHERE status = 'paid'
    GROUP BY HOUR(paid_at)
    ORDER BY hour
")->fetchAll();

// Total transactions
$totalTransactions = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'paid'")->fetchColumn();

// Prepare chart data
$dailyLabels = [];
$dailyData = [];
foreach ($dailyRevenue as $day) {
    $dailyLabels[] = date('d M', strtotime($day['date']));
    $dailyData[] = floatval($day['total']);
}

$monthlyLabels = [];
$monthlyData = [];
foreach ($monthlyRevenue as $month) {
    $monthlyLabels[] = $month['label'];
    $monthlyData[] = floatval($month['total']);
}

$methodLabels = [];
$methodData = [];
$methodColors = [];
$colorPalette = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6'];
$i = 0;
foreach ($methodBreakdown as $method) {
    $methodLabels[] = ucfirst($method['method']);
    $methodData[] = floatval($method['total']);
    $methodColors[] = $colorPalette[$i % count($colorPalette)];
    $i++;
}

$hourlyLabels = [];
$hourlyData = [];
for ($h = 0; $h < 24; $h++) {
    $hourlyLabels[] = sprintf('%02d:00', $h);
    $found = false;
    foreach ($hourlyRevenue as $hour) {
        if ($hour['hour'] == $h) {
            $hourlyData[] = intval($hour['transactions']);
            $found = true;
            break;
        }
    }
    if (!$found)
        $hourlyData[] = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Analytics - UniCycle Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        /* Revenue Dashboard Styles */
        .revenue-dashboard {
            display: grid;
            gap: 24px;
        }

        /* Hero Revenue Card */
        .revenue-hero {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            border-radius: 20px;
            padding: 32px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3);
        }

        .revenue-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 80%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 60%);
            pointer-events: none;
        }

        .revenue-hero::after {
            content: '\f201';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 30px;
            bottom: -20px;
            font-size: 150px;
            opacity: 0.1;
        }

        .revenue-hero-content {
            position: relative;
            z-index: 1;
        }

        .revenue-hero h2 {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .revenue-hero .amount {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -2px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: countUp 1s ease-out;
        }

        @keyframes countUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .revenue-hero .subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 8px;
        }

        /* Stats Cards Grid */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        @media (max-width: 1200px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .stat-card-enhanced {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card-enhanced:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        }

        .stat-card-enhanced .icon-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 16px;
        }

        .stat-card-enhanced.primary .icon-wrapper {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }

        .stat-card-enhanced.success .icon-wrapper {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
        }

        .stat-card-enhanced.warning .icon-wrapper {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: white;
        }

        .stat-card-enhanced.info .icon-wrapper {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
        }

        .stat-card-enhanced h4 {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .stat-card-enhanced .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
        }

        .stat-card-enhanced .trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 20px;
            margin-top: 10px;
        }

        .stat-card-enhanced .trend.up {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .stat-card-enhanced .trend.down {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        @media (max-width: 1024px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-header h3 i {
            color: #6366f1;
        }

        .chart-legend {
            display: flex;
            gap: 16px;
        }

        .chart-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .chart-legend-item .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .chart-container.small {
            height: 250px;
        }

        /* Payment Methods Cards */
        .methods-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 900px) {
            .methods-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .method-card-enhanced {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .method-card-enhanced:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        }

        .method-card-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .method-card-enhanced:nth-child(1)::before {
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
        }

        .method-card-enhanced:nth-child(2)::before {
            background: linear-gradient(90deg, #ec4899, #f472b6);
        }

        .method-card-enhanced:nth-child(3)::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .method-card-enhanced:nth-child(4)::before {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .method-card-enhanced .method-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.5rem;
        }

        .method-card-enhanced:nth-child(1) .method-icon {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }

        .method-card-enhanced:nth-child(2) .method-icon {
            background: rgba(236, 72, 153, 0.1);
            color: #ec4899;
        }

        .method-card-enhanced:nth-child(3) .method-icon {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .method-card-enhanced:nth-child(4) .method-icon {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .method-card-enhanced h4 {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .method-card-enhanced .amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
        }

        .method-card-enhanced .transactions {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 6px;
        }

        .method-card-enhanced .percentage {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 12px;
            background: #f3f4f6;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #6b7280;
        }

        /* Transactions Table */
        .transactions-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .transactions-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .transactions-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .transactions-header h3 i {
            color: #6366f1;
        }

        .view-all-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .view-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions-table th {
            background: #f9fafb;
            padding: 14px 20px;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .transactions-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.9rem;
            color: #374151;
        }

        .transactions-table tr:hover {
            background: #f9fafb;
        }

        .transactions-table .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .transactions-table .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .transactions-table .amount-cell {
            font-weight: 700;
            color: #10b981;
        }

        .transactions-table .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .transactions-table .status-badge.paid {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .transactions-table .status-badge.pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .transactions-table .method-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f3f4f6;
            border-radius: 8px;
            font-size: 0.8rem;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .no-data i {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .no-data p {
            font-size: 1rem;
        }

        /* Peak Hours Card */
        .peak-hours-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.2s;
        }

        .delay-3 {
            animation-delay: 0.3s;
        }

        .delay-4 {
            animation-delay: 0.4s;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h1><i class="fas fa-chart-pie" style="color: #6366f1; margin-right: 12px;"></i>Revenue Analytics</h1>
            <p>Track payments, revenue trends, and financial insights</p>
        </div>

        <div class="revenue-dashboard">
            <!-- Hero Revenue Card -->
            <div class="revenue-hero animate-in">
                <div class="revenue-hero-content">
                    <h2><i class="fas fa-coins"></i> Total Revenue</h2>
                    <div class="amount">RM <?= number_format($totalRevenue, 2) ?></div>
                    <div class="subtitle">
                        <i class="fas fa-info-circle"></i>
                        Lifetime earnings from all completed rentals
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-card-enhanced primary animate-in delay-1">
                    <div class="icon-wrapper">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h4>This Month</h4>
                    <div class="value">RM <?= number_format($monthRevenue, 2) ?></div>
                    <?php if ($growthPercentage != 0): ?>
                        <div class="trend <?= $growthPercentage > 0 ? 'up' : 'down' ?>">
                            <i class="fas fa-arrow-<?= $growthPercentage > 0 ? 'up' : 'down' ?>"></i>
                            <?= abs(round($growthPercentage, 1)) ?>% from last month
                        </div>
                    <?php endif; ?>
                </div>

                <div class="stat-card-enhanced success animate-in delay-2">
                    <div class="icon-wrapper">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h4>Total Transactions</h4>
                    <div class="value"><?= number_format($totalTransactions) ?></div>
                    <div class="trend up">
                        <i class="fas fa-check-circle"></i> Completed payments
                    </div>
                </div>

                <div class="stat-card-enhanced warning animate-in delay-3">
                    <div class="icon-wrapper">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4>Penalties Collected</h4>
                    <div class="value">RM <?= number_format($totalPenalties, 2) ?></div>
                    <div class="trend down">
                        <i class="fas fa-clock"></i> Late returns
                    </div>
                </div>

                <div class="stat-card-enhanced info animate-in delay-4">
                    <div class="icon-wrapper">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h4>Average Transaction</h4>
                    <div class="value">RM
                        <?= $totalTransactions > 0 ? number_format($totalRevenue / $totalTransactions, 2) : '0.00' ?>
                    </div>
                    <div class="trend up">
                        <i class="fas fa-chart-line"></i> Per rental
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Revenue Trend Chart -->
                <div class="chart-card animate-in">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-area"></i> Revenue Trend (Last 30 Days)</h3>
                        <div class="chart-legend">
                            <div class="chart-legend-item">
                                <span class="dot" style="background: #6366f1;"></span>
                                Daily Revenue
                            </div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Payment Methods Doughnut -->
                <div class="chart-card animate-in">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Payment Methods</h3>
                    </div>
                    <div class="chart-container small">
                        <canvas id="methodsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Monthly Comparison & Peak Hours -->
            <div class="charts-grid">
                <!-- Monthly Revenue Bar Chart -->
                <div class="chart-card animate-in">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-bar"></i> Monthly Revenue Comparison</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>

                <!-- Peak Hours -->
                <div class="peak-hours-card animate-in">
                    <div class="chart-header">
                        <h3><i class="fas fa-clock"></i> Peak Transaction Hours</h3>
                    </div>
                    <div class="chart-container small">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Payment Methods Cards -->
            <h3 style="margin: 10px 0 16px; color: #374151; font-weight: 600;">
                <i class="fas fa-credit-card" style="color: #6366f1; margin-right: 10px;"></i>Payment Methods Breakdown
            </h3>
            <div class="methods-grid">
                <?php
                $methodIcons = [
                    'cashless' => 'fas fa-mobile-alt',
                    'card' => 'fas fa-credit-card',
                    'ewallet' => 'fas fa-wallet',
                    'other' => 'fas fa-money-bill'
                ];
                $totalMethods = array_sum(array_column($methodBreakdown, 'total'));

                foreach ($methodBreakdown as $method):
                    $percentage = $totalMethods > 0 ? ($method['total'] / $totalMethods) * 100 : 0;
                    ?>
                    <div class="method-card-enhanced animate-in">
                        <div class="method-icon">
                            <i class="<?= $methodIcons[$method['method']] ?? 'fas fa-money-bill' ?>"></i>
                        </div>
                        <h4><?= ucfirst($method['method']) ?></h4>
                        <div class="amount">RM <?= number_format($method['total'], 2) ?></div>
                        <div class="transactions"><?= $method['count'] ?> transactions</div>
                        <div class="percentage"><?= round($percentage, 1) ?>%</div>
                    </div>
                <?php endforeach; ?>

                <?php if (count($methodBreakdown) === 0): ?>
                    <div class="method-card-enhanced" style="grid-column: span 4;">
                        <div class="method-icon" style="background: #f3f4f6; color: #9ca3af;">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <h4>No Payments Yet</h4>
                        <div class="amount">RM 0.00</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Transactions -->
            <div class="transactions-card animate-in" style="margin-top: 10px;">
                <div class="transactions-header">
                    <h3><i class="fas fa-history"></i> Recent Transactions</h3>
                </div>

                <?php if (count($payments) > 0): ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Transaction</th>
                                <th>User</th>
                                <th>Bike</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($payment['rental_code']) ?></strong></td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?= strtoupper(substr($payment['student_name'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($payment['student_name']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($payment['bike_code']) ?></td>
                                    <td>
                                        <span class="method-badge">
                                            <i class="<?= $methodIcons[$payment['method']] ?? 'fas fa-money-bill' ?>"></i>
                                            <?= ucfirst($payment['method']) ?>
                                        </span>
                                    </td>
                                    <td class="amount-cell">RM <?= number_format($payment['amount'], 2) ?></td>
                                    <td>
                                        <span class="status-badge <?= $payment['status'] ?>">
                                            <?= ucfirst($payment['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y, h:i A', strtotime($payment['paid_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-receipt"></i>
                        <p>No transactions recorded yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Chart.js Configuration
        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.color = '#6b7280';

        // Revenue Trend Line Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const gradient = revenueCtx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($dailyLabels) ?>,
                datasets: [{
                    label: 'Revenue (RM)',
                    data: <?= json_encode($dailyData) ?>,
                    borderColor: '#6366f1',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: 'white',
                        bodyColor: 'white',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: (context) => `RM ${context.raw.toFixed(2)}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 10 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            callback: (value) => `RM ${value}`
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Payment Methods Doughnut Chart
        new Chart(document.getElementById('methodsChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($methodLabels) ?>,
                datasets: [{
                    data: <?= json_encode($methodData) ?>,
                    backgroundColor: <?= json_encode($methodColors) ?>,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (context) => `${context.label}: RM ${context.raw.toFixed(2)}`
                        }
                    }
                }
            }
        });

        // Monthly Revenue Bar Chart
        const monthlyGradient = document.getElementById('monthlyChart').getContext('2d').createLinearGradient(0, 0, 0, 300);
        monthlyGradient.addColorStop(0, '#8b5cf6');
        monthlyGradient.addColorStop(1, '#6366f1');

        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthlyLabels) ?>,
                datasets: [{
                    label: 'Monthly Revenue',
                    data: <?= json_encode($monthlyData) ?>,
                    backgroundColor: monthlyGradient,
                    borderRadius: 8,
                    maxBarThickness: 60
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (context) => `RM ${context.raw.toFixed(2)}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            callback: (value) => `RM ${value}`
                        }
                    }
                }
            }
        });

        // Peak Hours Bar Chart
        const hourlyGradient = document.getElementById('hourlyChart').getContext('2d').createLinearGradient(0, 0, 0, 200);
        hourlyGradient.addColorStop(0, '#10b981');
        hourlyGradient.addColorStop(1, '#34d399');

        new Chart(document.getElementById('hourlyChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($hourlyLabels) ?>,
                datasets: [{
                    label: 'Transactions',
                    data: <?= json_encode($hourlyData) ?>,
                    backgroundColor: hourlyGradient,
                    borderRadius: 4,
                    maxBarThickness: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (context) => `${context.raw} transactions`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxTicksLimit: 12,
                            callback: function (value, index) {
                                return index % 3 === 0 ? this.getLabelForValue(value) : '';
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>

</html>