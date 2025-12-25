<?php
/**
 * Admin Complaints Management
 * View and manage all user complaints
 */
require_once 'includes/auth_check.php';

$message = '';
$messageType = '';

// Handle Status Update
if (isset($_POST['update_status'])) {
    $complaint_id = $_POST['complaint_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE complaint_id = ?");
    $stmt->execute([$status, $complaint_id]);
    $message = 'Complaint status updated!';
    $messageType = 'success';
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = '';
switch ($filter) {
    case 'open':
        $where = "WHERE c.status = 'open'";
        break;
    case 'resolved':
        $where = "WHERE c.status = 'resolved'";
        break;
}

$complaints = $pdo->query("
    SELECT c.*, s.name as student_name, s.email,
           r.rental_code, b.bike_code
    FROM complaints c 
    JOIN students s ON c.student_id = s.student_id 
    LEFT JOIN rentals r ON c.rental_id = r.rental_id
    LEFT JOIN bikes b ON r.bike_id = b.bike_id
    $where
    ORDER BY c.created_at DESC
")->fetchAll();

// Counts
$counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn(),
    'open' => $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'open'")->fetchColumn(),
    'resolved' => $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'resolved'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints - UniCycle Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .message {
            padding: 14px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .complaint-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--warning);
        }

        .complaint-card.resolved {
            border-left-color: var(--success);
        }

        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .complaint-meta {
            display: flex;
            gap: 16px;
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-bottom: 12px;
        }

        .complaint-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .complaint-message {
            background: var(--gray-50);
            padding: 16px;
            border-radius: 8px;
            color: var(--gray-700);
            margin-bottom: 16px;
        }

        .complaint-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h1>Complaints</h1>
            <p>Manage and resolve user complaints</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-comment-dots"></i></div>
                <h3>Total Complaints</h3>
                <div class="value"><?= $counts['all'] ?></div>
            </div>
            <div class="stat-card warning">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h3>Open</h3>
                <div class="value"><?= $counts['open'] ?></div>
            </div>
            <div class="stat-card success">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <h3>Resolved</h3>
                <div class="value"><?= $counts['resolved'] ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All
                (<?= $counts['all'] ?>)</a>
            <a href="?filter=open" class="filter-btn <?= $filter === 'open' ? 'active' : '' ?>">Open
                (<?= $counts['open'] ?>)</a>
            <a href="?filter=resolved" class="filter-btn <?= $filter === 'resolved' ? 'active' : '' ?>">Resolved
                (<?= $counts['resolved'] ?>)</a>
        </div>

        <!-- Complaints List -->
        <?php foreach ($complaints as $complaint): ?>
            <div class="complaint-card <?= $complaint['status'] ?>">
                <div class="complaint-header">
                    <div>
                        <strong style="font-size: 1.1rem;"><?= htmlspecialchars($complaint['complaint_code']) ?></strong>
                        <span class="badge <?= $complaint['status'] ?>"
                            style="margin-left: 10px;"><?= ucfirst($complaint['status']) ?></span>
                    </div>
                    <span style="color: var(--gray-500); font-size: 0.85rem;">
                        <?= date('d M Y, h:i A', strtotime($complaint['created_at'])) ?>
                    </span>
                </div>

                <div class="complaint-meta">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($complaint['student_name']) ?>
                        (<?= htmlspecialchars($complaint['email']) ?>)</span>
                    <?php if ($complaint['bike_code']): ?>
                        <span><i class="fas fa-bicycle"></i> <?= htmlspecialchars($complaint['bike_code']) ?></span>
                    <?php endif; ?>
                    <?php if ($complaint['rental_code']): ?>
                        <span><i class="fas fa-receipt"></i> <?= htmlspecialchars($complaint['rental_code']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="complaint-message">
                    <?= nl2br(htmlspecialchars($complaint['message'])) ?>
                </div>

                <form method="POST" class="complaint-actions">
                    <input type="hidden" name="complaint_id" value="<?= $complaint['complaint_id'] ?>">
                    <select name="status" class="form-control" style="width: auto; padding: 8px 16px;">
                        <option value="open" <?= $complaint['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="resolved" <?= $complaint['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>
            </div>
        <?php endforeach; ?>

        <?php if (count($complaints) === 0): ?>
            <div class="content-card">
                <div class="empty-state">
                    <i class="fas fa-comment-dots"></i>
                    <p>No complaints found</p>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>