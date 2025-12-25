<?php
session_start();
require_once "config.php";

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

// Get current user data
$stmt = $pdo->prepare("SELECT name, email, phone, profile_pic FROM students WHERE student_id=?");
$stmt->execute([$student_id]);
$user = $stmt->fetch();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate name
    if (empty($newName)) {
        $error = 'Name is required.';
    } else {
        // Update name and phone
        $stmt = $pdo->prepare("UPDATE students SET name=?, phone=? WHERE student_id=?");
        $stmt->execute([$newName, $newPhone ?: null, $student_id]);
        $_SESSION['student_name'] = $newName;
        $student_name = $newName;

        // Handle password change
        if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error = 'All password fields are required to change password.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 6) {
                $error = 'New password must be at least 6 characters.';
            } else {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM students WHERE student_id=?");
                $stmt->execute([$student_id]);
                $currentHash = $stmt->fetchColumn();

                if (!password_verify($currentPassword, $currentHash)) {
                    $error = 'Current password is incorrect.';
                } else {
                    // Update password
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE students SET password=? WHERE student_id=?");
                    $stmt->execute([$newHash, $student_id]);
                    $success = 'Password updated successfully!';
                }
            }
        }

        // Handle profile picture upload
        if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
            $croppedImage = $_POST['cropped_image'];

            // Check if it's a valid base64 data URL
            if (strpos($croppedImage, 'data:image') === 0) {
                // Decode base64 image
                $imageData = explode(',', $croppedImage);
                if (count($imageData) === 2) {
                    $imageDecoded = base64_decode($imageData[1]);
                    if ($imageDecoded !== false) {
                        $fileName = 'profile_' . $student_id . '_' . time() . '.png';
                        $filePath = __DIR__ . '/assets/uploads/' . $fileName;

                        // Delete old profile pic if exists
                        if (!empty($user['profile_pic'])) {
                            $oldPath = __DIR__ . '/assets/uploads/' . $user['profile_pic'];
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }

                        // Save new image
                        if (file_put_contents($filePath, $imageDecoded)) {
                            $stmt = $pdo->prepare("UPDATE students SET profile_pic=? WHERE student_id=?");
                            $stmt->execute([$fileName, $student_id]);
                            $user['profile_pic'] = $fileName;
                            $success = 'Profile picture updated!';
                        } else {
                            $error = 'Failed to save image file.';
                        }
                    }
                }
            }
        }

        if (empty($error)) {
            $success = $success ?: 'Settings saved successfully!';
        }

        // Refresh user data
        $stmt = $pdo->prepare("SELECT name, email, phone, profile_pic FROM students WHERE student_id=?");
        $stmt->execute([$student_id]);
        $user = $stmt->fetch();
    }
}

$currentDate = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Settings - UniCycle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css?v=8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        .settings-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .settings-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .settings-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .settings-header p {
            color: #64748b;
            font-size: 14px;
        }

        /* Profile Picture Section */
        .profile-section {
            text-align: center;
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid #e2e8f0;
        }

        .profile-pic-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 16px;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e2e8f0;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: 600;
        }

        .profile-pic img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #f1f5f9;
            color: #64748b;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .profile-upload-btn:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        #imageInput {
            display: none;
        }

        /* Form Styles */
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

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
        }

        .form-group .hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 6px;
        }

        .form-group input:disabled {
            background: #f8fafc;
            color: #64748b;
        }

        /* Section Divider */
        .section-divider {
            margin: 28px 0;
            padding-top: 28px;
            border-top: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Save Button */
        .save-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        }

        /* Messages */
        .success-msg,
        .error-msg {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .success-msg {
            background: #d1fae5;
            color: #065f46;
        }

        .error-msg {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Crop Modal */
        .crop-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .crop-modal.active {
            display: flex;
        }

        .crop-modal-box {
            background: white;
            border-radius: 20px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
        }

        .crop-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .crop-modal-header h3 {
            font-size: 18px;
            font-weight: 600;
        }

        .crop-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }

        .crop-container {
            max-height: 400px;
            overflow: hidden;
        }

        .crop-container img {
            max-width: 100%;
        }

        .crop-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        .crop-actions button {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .crop-cancel {
            background: #f1f5f9;
            color: #64748b;
            border: none;
        }

        .crop-confirm {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
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
            <div class="user-avatar">
                <?php if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/assets/uploads/' . $user['profile_pic'])): ?>
                    <img src="assets/uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile"
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
            <a href="complaints.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-comment-dots"></i></span>
                <span>Complaints</span>
            </a>
            <a href="settings.php" class="nav-item active">
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
                <h1>Settings</h1>
                <p class="banner-date"><?= $currentDate ?></p>
            </div>
        </div>

        <!-- Content -->
        <div class="dashboard-content" style="display: block;">
            <div class="settings-container">
                <div class="settings-card">
                    <div class="settings-header">
                        <h2><i class="fas fa-user-cog"></i> Profile Settings</h2>
                        <p>Manage your account information</p>
                    </div>

                    <?php if ($success): ?>
                        <div class="success-msg">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="error-msg">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="settingsForm">
                        <!-- Profile Picture -->
                        <div class="profile-section">
                            <div class="profile-pic-wrapper">
                                <div class="profile-pic" id="profileDisplay">
                                    <?php if (!empty($user['profile_pic']) && file_exists('assets/uploads/' . $user['profile_pic'])): ?>
                                        <img src="assets/uploads/<?= htmlspecialchars($user['profile_pic']) ?>"
                                            alt="Profile">
                                    <?php else: ?>
                                        <?= htmlspecialchars($initials) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="button" class="profile-upload-btn"
                                onclick="document.getElementById('imageInput').click()">
                                <i class="fas fa-camera"></i> Change Photo
                            </button>
                            <input type="file" id="imageInput" accept="image/*">
                            <input type="hidden" name="cropped_image" id="croppedImage">
                        </div>

                        <!-- Basic Info -->
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Username</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <div class="hint">Email cannot be changed</div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number (Emergency)</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                placeholder="e.g. 012-3456789">
                            <div class="hint">For emergency contact purposes</div>
                        </div>

                        <!-- Password Section -->
                        <div class="section-divider">
                            <div class="section-title">
                                <i class="fas fa-lock"></i> Change Password
                            </div>

                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" placeholder="Enter current password">
                            </div>

                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" placeholder="Enter new password">
                            </div>

                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" placeholder="Confirm new password">
                            </div>

                            <div class="hint">Leave password fields empty if you don't want to change it</div>
                        </div>

                        <button type="submit" class="save-btn">
                            <i class="fas fa-check"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Crop Modal -->
    <div class="crop-modal" id="cropModal">
        <div class="crop-modal-box">
            <div class="crop-modal-header">
                <h3><i class="fas fa-crop"></i> Crop Image</h3>
                <button class="crop-modal-close" onclick="closeCropModal()">&times;</button>
            </div>
            <div class="crop-container">
                <img id="cropImage" src="">
            </div>
            <div class="crop-actions">
                <button class="crop-cancel" onclick="closeCropModal()">Cancel</button>
                <button class="crop-confirm" onclick="confirmCrop()">
                    <i class="fas fa-check"></i> Apply
                </button>
            </div>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        let cropper = null;

        // Image upload handling
        document.getElementById('imageInput').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    const cropImage = document.getElementById('cropImage');
                    cropImage.src = event.target.result;
                    document.getElementById('cropModal').classList.add('active');

                    // Initialize cropper after image loads
                    cropImage.onload = function () {
                        if (cropper) {
                            cropper.destroy();
                        }
                        cropper = new Cropper(cropImage, {
                            aspectRatio: 1,
                            viewMode: 1,
                            dragMode: 'move',
                            autoCropArea: 1,
                            restore: false,
                            guides: true,
                            center: true,
                            highlight: false,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: false,
                        });
                    };
                };
                reader.readAsDataURL(file);
            }
        });

        function closeCropModal() {
            document.getElementById('cropModal').classList.remove('active');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            document.getElementById('imageInput').value = '';
        }

        function confirmCrop() {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                const croppedDataUrl = canvas.toDataURL('image/png');
                document.getElementById('croppedImage').value = croppedDataUrl;

                // Update preview
                const profileDisplay = document.getElementById('profileDisplay');
                profileDisplay.innerHTML = `<img src="${croppedDataUrl}" alt="Profile">`;

                closeCropModal();
            }
        }

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
                hideLogoutModal();
                closeCropModal();
            }
        });
    </script>

</body>

</html>