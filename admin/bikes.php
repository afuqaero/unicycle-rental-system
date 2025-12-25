<?php
/**
 * Admin Bike Management
 * Add, edit, delete, and manage bike inventory
 */
require_once 'includes/auth_check.php';

$message = '';
$messageType = '';

// Handle Add Bike
if (isset($_POST['add_bike'])) {
    $bike_code = trim($_POST['bike_code']);
    $bike_name = trim($_POST['bike_name']);
    $bike_type = $_POST['bike_type'];
    $status = $_POST['status'];
    $location = trim($_POST['location']) ?: 'Main Bike Area';

    // Check if bike code already exists
    $check = $pdo->prepare("SELECT bike_id FROM bikes WHERE bike_code = ?");
    $check->execute([$bike_code]);

    if ($check->fetch()) {
        $message = 'Bike code already exists!';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare("INSERT INTO bikes (bike_code, bike_name, bike_type, status, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$bike_code, $bike_name, $bike_type, $status, $location]);
        $message = 'Bike added successfully!';
        $messageType = 'success';
    }
}

// Handle Update Bike
if (isset($_POST['update_bike'])) {
    $bike_id = $_POST['bike_id'];
    $bike_type = $_POST['bike_type'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE bikes SET bike_type = ?, status = ? WHERE bike_id = ?");
    $stmt->execute([$bike_type, $status, $bike_id]);
    $message = 'Bike updated successfully!';
    $messageType = 'success';
}

// Handle Delete Bike
if (isset($_POST['delete_bike'])) {
    $bike_id = $_POST['bike_id'];

    $stmt = $pdo->prepare("DELETE FROM bikes WHERE bike_id = ?");
    $stmt->execute([$bike_id]);
    $message = 'Bike deleted successfully!';
    $messageType = 'success';
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = '';
switch ($filter) {
    case 'available':
        $where = "WHERE status = 'available'";
        break;
    case 'maintenance':
        $where = "WHERE status = 'maintenance'";
        break;
    case 'rented':
        $where = "WHERE status = 'rented'";
        break;
    case 'pending':
        $where = "WHERE status = 'pending'";
        break;
}

$bikes = $pdo->query("SELECT * FROM bikes $where ORDER BY bike_code")->fetchAll();

// Counts
$counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM bikes")->fetchColumn(),
    'available' => $pdo->query("SELECT COUNT(*) FROM bikes WHERE status = 'available'")->fetchColumn(),
    'maintenance' => $pdo->query("SELECT COUNT(*) FROM bikes WHERE status = 'maintenance'")->fetchColumn(),
    'rented' => $pdo->query("SELECT COUNT(*) FROM bikes WHERE status = 'rented'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bike Management - UniCycle Admin</title>
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

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .bike-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .bike-meta {
            display: flex;
            gap: 16px;
            margin-top: 8px;
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        .bike-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h1>Bike Management</h1>
            <p>Add, edit, and manage your bike inventory</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Add Bike Form -->
        <form method="POST" class="add-form-box">
            <div class="form-group">
                <label>Bike Code</label>
                <input type="text" name="bike_code" class="form-control" placeholder="e.g. MTB-016" required>
            </div>
            <div class="form-group">
                <label>Bike Name</label>
                <input type="text" name="bike_name" class="form-control" placeholder="e.g. Mountain Bike #16" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="bike_type" class="form-control">
                    <option value="mountain">Mountain</option>
                    <option value="city">City</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="available">Available</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" placeholder="Main Bike Area">
            </div>
            <button type="submit" name="add_bike" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Bike
            </button>
        </form>

        <!-- Filters -->
        <div class="filters-bar">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                All (<?= $counts['all'] ?>)
            </a>
            <a href="?filter=available" class="filter-btn <?= $filter === 'available' ? 'active' : '' ?>">
                Available (<?= $counts['available'] ?>)
            </a>
            <a href="?filter=maintenance" class="filter-btn <?= $filter === 'maintenance' ? 'active' : '' ?>">
                Maintenance (<?= $counts['maintenance'] ?>)
            </a>
            <a href="?filter=rented" class="filter-btn <?= $filter === 'rented' ? 'active' : '' ?>">
                Rented (<?= $counts['rented'] ?>)
            </a>
        </div>

        <!-- Bikes Grid -->
        <div class="cards-grid">
            <?php foreach ($bikes as $bike): ?>
                <div class="bike-card">
                    <span class="badge status-badge <?= $bike['status'] ?>"><?= ucfirst($bike['status']) ?></span>
                    <h3><?= htmlspecialchars($bike['bike_code']) ?></h3>
                    <p><?= htmlspecialchars($bike['bike_name']) ?></p>
                    <div class="bike-meta">
                        <span><i class="fas fa-tag"></i> <?= ucfirst($bike['bike_type']) ?></span>
                        <span><i class="fas fa-location-dot"></i> <?= htmlspecialchars($bike['location']) ?></span>
                    </div>

                    <form method="POST" class="bike-actions">
                        <input type="hidden" name="bike_id" value="<?= $bike['bike_id'] ?>">
                        <select name="bike_type" class="form-control" style="flex: 1; padding: 8px;">
                            <option value="mountain" <?= $bike['bike_type'] === 'mountain' ? 'selected' : '' ?>>Mountain
                            </option>
                            <option value="city" <?= $bike['bike_type'] === 'city' ? 'selected' : '' ?>>City</option>
                            <option value="other" <?= $bike['bike_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                        <select name="status" class="form-control" style="flex: 1; padding: 8px;">
                            <option value="available" <?= $bike['status'] === 'available' ? 'selected' : '' ?>>Available
                            </option>
                            <option value="maintenance" <?= $bike['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance
                            </option>
                            <option value="rented" <?= $bike['status'] === 'rented' ? 'selected' : '' ?>>Rented</option>
                        </select>
                        <button type="submit" name="update_bike" class="btn btn-primary btn-icon" title="Update">
                            <i class="fas fa-save"></i>
                        </button>
                        <button type="submit" name="delete_bike" class="btn btn-danger btn-icon" title="Delete"
                            onclick="return confirm('Delete this bike permanently?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php if (count($bikes) === 0): ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fas fa-bicycle"></i>
                    <p>No bikes found</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>