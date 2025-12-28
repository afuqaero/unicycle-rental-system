<?php
/**
 * Admin Dashboard
 * Main overview page for administrators with analytics charts
 */
require_once 'includes/auth_check.php';

// Dashboard statistics
$available_bikes = $pdo->query("SELECT COUNT(*) FROM bikes WHERE status = 'available'")->fetchColumn();
$rented_bikes = $pdo->query("SELECT COUNT(*) FROM bikes WHERE status = 'rented'")->fetchColumn();
$maintenance_bikes = $pdo->query("SELECT COUNT(*) FROM bikes WHERE status = 'maintenance'")->fetchColumn();
$active_rentals = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn();
$completed_rentals = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'completed'")->fetchColumn();
$late_rentals = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'late'")->fetchColumn();
$total_rentals = $pdo->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM students WHERE role = 'user'")->fetchColumn();
$pending_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'open'")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'")->fetchColumn();
$penalty_revenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM penalties WHERE status = 'paid'")->fetchColumn();

// Bike types count
$mountain_bikes = $pdo->query("SELECT COUNT(*) FROM bikes WHERE bike_type = 'mountain'")->fetchColumn();
$city_bikes = $pdo->query("SELECT COUNT(*) FROM bikes WHERE bike_type = 'city'")->fetchColumn();

// Rentals per day (last 7 days)
$rentals_by_day = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM rentals 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll();

// Revenue per day (last 7 days) 
$revenue_by_day = $pdo->query("
    SELECT DATE(paid_at) as date, SUM(amount) as total 
    FROM payments 
    WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 'paid'
    GROUP BY DATE(paid_at)
    ORDER BY date ASC
")->fetchAll();

// Prepare chart data
$last7Days = [];
$rentalCounts = [];
$revenueCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime("-$i days"));
    $last7Days[] = $dayName;

    // Find rental count for this date
    $found = false;
    foreach ($rentals_by_day as $r) {
        if ($r['date'] === $date) {
            $rentalCounts[] = (int) $r['count'];
            $found = true;
            break;
        }
    }
    if (!$found)
        $rentalCounts[] = 0;

    // Find revenue for this date
    $found = false;
    foreach ($revenue_by_day as $r) {
        if ($r['date'] === $date) {
            $revenueCounts[] = (float) $r['total'];
            $found = true;
            break;
        }
    }
    if (!$found)
        $revenueCounts[] = 0;
}

// Recent rentals
$recent_rentals = $pdo->query("
    SELECT r.*, s.name as student_name, b.bike_code 
    FROM rentals r 
    JOIN students s ON r.student_id = s.student_id 
    JOIN bikes b ON r.bike_id = b.bike_id 
    ORDER BY r.created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UniCycle</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 30px;
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
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .chart-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-card h3 i {
            color: var(--primary);
        }

        .chart-container {
            position: relative;
            height: 250px;
        }

        .mini-charts {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .mini-charts {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .mini-chart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .mini-chart-card .chart-small {
            width: 60px;
            height: 60px;
        }

        .mini-chart-card .info h4 {
            font-size: 0.85rem;
            color: var(--gray-500);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .mini-chart-card .info .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            border-radius: 16px;
            color: white;
            margin-bottom: 30px;
        }

        .stats-summary .stat-item {
            text-align: center;
            padding: 16px;
        }

        .stats-summary .stat-item h4 {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .stats-summary .stat-item .value {
            font-size: 2rem;
            font-weight: 700;
        }

        .stats-summary .stat-item .change {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION['student_name'] ?? 'Admin') ?>! Here's your analytics
                overview.</p>
        </div>

        <!-- Revenue Summary -->
        <div class="stats-summary">
            <div class="stat-item">
                <h4><i class="fas fa-dollar-sign"></i> Total Revenue</h4>
                <div class="value">RM <?= number_format($total_revenue, 2) ?></div>
                <div class="change">From rentals</div>
            </div>
            <div class="stat-item">
                <h4><i class="fas fa-exclamation-triangle"></i> Penalty Revenue</h4>
                <div class="value">RM <?= number_format($penalty_revenue, 2) ?></div>
                <div class="change">From late returns</div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-bicycle"></i></div>
                <h3>Available Bikes</h3>
                <div class="value"><?= $available_bikes ?></div>
            </div>

            <div class="stat-card success">
                <div class="icon"><i class="fas fa-bolt"></i></div>
                <h3>Active Rentals</h3>
                <div class="value"><?= $active_rentals ?></div>
            </div>

            <div class="stat-card secondary">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3>Total Users</h3>
                <div class="value"><?= $total_users ?></div>
            </div>

            <div class="stat-card warning">
                <div class="icon"><i class="fas fa-comment-dots"></i></div>
                <h3>Open Complaints</h3>
                <div class="value"><?= $pending_complaints ?></div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Rentals Trend Chart -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Rentals This Week</h3>
                <div class="chart-container">
                    <canvas id="rentalsChart"></canvas>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Revenue This Week</h3>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Bike Types Distribution -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Bike Types</h3>
                <div class="chart-container">
                    <canvas id="bikeTypesChart"></canvas>
                </div>
            </div>

            <!-- Rental Status Distribution -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Rental Status</h3>
                <div class="chart-container">
                    <canvas id="rentalStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Bike Status Mini Charts -->
        <div class="mini-charts">
            <div class="mini-chart-card">
                <div class="chart-small">
                    <canvas id="availableChart"></canvas>
                </div>
                <div class="info">
                    <h4>Available</h4>
                    <div class="value"><?= $available_bikes ?></div>
                </div>
            </div>
            <div class="mini-chart-card">
                <div class="chart-small">
                    <canvas id="rentedChart"></canvas>
                </div>
                <div class="info">
                    <h4>Rented</h4>
                    <div class="value"><?= $rented_bikes ?></div>
                </div>
            </div>
            <div class="mini-chart-card">
                <div class="chart-small">
                    <canvas id="maintenanceChart"></canvas>
                </div>
                <div class="info">
                    <h4>Maintenance</h4>
                    <div class="value"><?= $maintenance_bikes ?></div>
                </div>
            </div>
            <div class="mini-chart-card">
                <div class="chart-small">
                    <canvas id="totalRentalsChart"></canvas>
                </div>
                <div class="info">
                    <h4>Total Rentals</h4>
                    <div class="value"><?= $total_rentals ?></div>
                </div>
            </div>
        </div>

        <!-- Recent Rentals -->
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-clock" style="margin-right: 10px; color: var(--primary);"></i>Recent Rentals</h2>
                <a href="rentals.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($recent_rentals) > 0): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Rental Code</th>
                                <th>User</th>
                                <th>Bike</th>
                                <th>Start Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_rentals as $rental): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($rental['rental_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($rental['student_name']) ?></td>
                                    <td><?= htmlspecialchars($rental['bike_code']) ?></td>
                                    <td><?= date('d M Y, h:i A', strtotime($rental['start_time'])) ?></td>
                                    <td><span class="badge <?= $rental['status'] ?>"><?= ucfirst($rental['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No rentals yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card primary">
                <i class="fas fa-bicycle"></i>
                <h3>Manage Bikes</h3>
                <p>Add, edit, or remove bikes from the system</p>
                <a href="bikes.php">Go to Bikes →</a>
            </div>

            <div class="action-card secondary">
                <i class="fas fa-users"></i>
                <h3>Manage Users</h3>
                <p>View and manage registered users</p>
                <a href="users.php">Go to Users →</a>
            </div>
        </div>
    </main>

    <script>
        // Chart.js defaults
        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.plugins.legend.display = false;

        // Rentals Line Chart
        new Chart(document.getElementById('rentalsChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($last7Days) ?>,
                datasets: [{
                    label: 'Rentals',
                    data: <?= json_encode($rentalCounts) ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Revenue Bar Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($last7Days) ?>,
                datasets: [{
                    label: 'Revenue (RM)',
                    data: <?= json_encode($revenueCounts) ?>,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(192, 132, 252, 0.8)',
                        'rgba(167, 139, 250, 0.8)',
                        'rgba(129, 140, 248, 0.8)',
                        'rgba(99, 102, 241, 0.8)'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return 'RM ' + value;
                            }
                        }
                    }
                }
            }
        });

        // Bike Types Doughnut Chart
        new Chart(document.getElementById('bikeTypesChart'), {
            type: 'doughnut',
            data: {
                labels: ['Mountain Bikes', 'City Bikes'],
                datasets: [{
                    data: [<?= $mountain_bikes ?>, <?= $city_bikes ?>],
                    backgroundColor: ['#6366f1', '#22c55e'],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });

        // Rental Status Doughnut Chart
        new Chart(document.getElementById('rentalStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Completed', 'Late'],
                datasets: [{
                    data: [<?= $active_rentals ?>, <?= $completed_rentals ?>, <?= $late_rentals ?>],
                    backgroundColor: ['#3b82f6', '#22c55e', '#ef4444'],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });

        // Mini Progress Charts
        const createProgressChart = (id, value, max, color) => {
            const percentage = max > 0 ? (value / max * 100) : 0;
            new Chart(document.getElementById(id), {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [percentage, 100 - percentage],
                        backgroundColor: [color, '#e5e7eb'],
                        borderWidth: 0,
                        cutout: '75%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } }
                }
            });
        };

        const totalBikes = <?= $available_bikes + $rented_bikes + $maintenance_bikes ?>;
        createProgressChart('availableChart', <?= $available_bikes ?>, totalBikes, '#22c55e');
        createProgressChart('rentedChart', <?= $rented_bikes ?>, totalBikes, '#3b82f6');
        createProgressChart('maintenanceChart', <?= $maintenance_bikes ?>, totalBikes, '#f59e0b');
        createProgressChart('totalRentalsChart', <?= $total_rentals ?>, <?= $total_rentals + 10 ?>, '#6366f1');
    </script>
</body>

</html>