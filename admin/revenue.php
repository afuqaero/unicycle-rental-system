<?php
/**
 * Admin Revenue Management
 * View payment and revenue statistics
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
    LIMIT 20
")->fetchAll();

// Daily revenue for chart (last 7 days)
$dailyRevenue = $pdo->query("
    SELECT DATE(paid_at) as date, SUM(amount) as total
    FROM payments WHERE status = 'paid' AND paid_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
    GROUP BY DATE(paid_at)
    ORDER BY date
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue - UniCycle Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .revenue-highlight {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 24px;
        }

        .revenue-highlight h2 {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .revenue-highlight .amount {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .method-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
        }

        .method-card .method-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.2rem;
        }

        .method-card h4 {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .method-card .value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--gray-800);
        }

        .method-card .count {
            font-size: 0.8rem;
            color: var(--gray-500);
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h1>Revenue</h1>
            <p>Track payments and financial statistics</p>
        </div>

        <!-- Revenue Cards -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="revenue-highlight" style="grid-column: span 2;">
                <h2><i class="fas fa-chart-line" style="margin-right: 8px;"></i>Total Revenue</h2>
                <div class="amount">RM <?= number_format($totalRevenue, 2) ?></div>
            </div>
            <div>
                <div class="stat-card success" style="margin-bottom: 16px;">
                    <div class="icon"><i class="fas fa-calendar"></i></div>
                    <h3>This Month</h3>
                    <div class="value">RM <?= number_format($monthRevenue, 2) ?></div>
                </div>
                <div class="stat-card warning">
                    <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                    <h3>Penalties Collected</h3>
                    <div class="value">RM <?= number_format($totalPenalties, 2) ?></div>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <h3 style="margin: 30px 0 16px; color: var(--gray-700);">Payment Methods</h3>
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
            <?php
            $methodIcons = [
                'cashless' => 'fas fa-mobile-alt',
                'card' => 'fas fa-credit-card',
                'ewallet' => 'fas fa-wallet',
                'other' => 'fas fa-money-bill'
            ];
            foreach ($methodBreakdown as $method):
                ?>
                <div class="method-card">
                    <div class="method-icon">
                        <i class="<?= $methodIcons[$method['method']] ?? 'fas fa-money-bill' ?>"></i>
                    </div>
                    <h4><?= ucfirst($method['method']) ?></h4>
                    <div class="value">RM <?= number_format($method['total'], 2) ?></div>
                    <div class="count"><?= $method['count'] ?> transactions</div>
                </div>
            <?php endforeach; ?>

            <?php if (count($methodBreakdown) === 0): ?>
                <div class="method-card" style="grid-column: span 4;">
                    <div class="method-icon"><i class="fas fa-receipt"></i></div>
                    <h4>No Payments Yet</h4>
                    <div class="value">RM 0.00</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Payments -->
        <div class="content-card" style="margin-top: 30px;">
            <div class="card-header">
                <h2><i class="fas fa-receipt" style="margin-right: 10px; color: var(--primary);"></i>Recent Payments
                </h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Rental</th>
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
                                <td><?= htmlspecialchars($payment['student_name']) ?></td>
                                <td><?= htmlspecialchars($payment['bike_code']) ?></td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="<?= $methodIcons[$payment['method']] ?? 'fas fa-money-bill' ?>"
                                            style="color: var(--gray-500);"></i>
                                        <?= ucfirst($payment['method']) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: var(--success);">RM
                                    <?= number_format($payment['amount'], 2) ?></td>
                                <td><span class="badge <?= $payment['status'] ?>"><?= ucfirst($payment['status']) ?></span>
                                </td>
                                <td><?= date('d M Y, h:i A', strtotime($payment['paid_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (count($payments) === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>No payments yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>