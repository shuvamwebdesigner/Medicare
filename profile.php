<?php

include 'config/db.php';
include 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$user = null;
$error = '';
$success = '';

// Fetch current user data with error handling
if ($userId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
        } else {
            $error = "User data not found.";
        }
    } else {
        $error = "Database error. Please try again.";
    }
    $stmt->close();
} else {
    $error = "Invalid user session.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        
        // Validation
        if (empty($name)) {
            $error = "Full name is required.";
        } elseif (strlen($name) > 255) {
            $error = "Full name is too long.";
        } elseif (strlen($address) > 500) {
            $error = "Address is too long.";
        } elseif (!empty($phone) && !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
            $error = "Please enter a valid phone number.";
        } else {
            // Update profile
            $stmt = $conn->prepare("UPDATE users SET name = ?, address = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $address, $phone, $userId);
            
            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                // Update local user data
                $user['name'] = $name;
                $user['address'] = $address;
                $user['phone'] = $phone;
            } else {
                $error = "Failed to update profile. Please try again.";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($current_password)) {
            $error = "Current password is required.";
        } elseif (empty($new_password)) {
            $error = "New password is required.";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $userId);
            
            if ($stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to change password. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="icon" href="admin/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/footer.css">
    <link rel="stylesheet" href="assets/profile.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container profile-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1><i class="fas fa-user me-3"></i>My Profile</h1>
            <p>Manage your account information</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success animate__animated animate__fadeIn">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-8 mb-4">
                    <div class="card profile-card animate__animated animate__zoomIn animate__delay-1s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-user me-1"></i>Full Name
                                        </label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required maxlength="255">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>Email Address
                                        </label>
                                        <input type="email" class="form-control" id="email" 
                                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                                        <small class="form-text text-muted">Email cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Address
                                    </label>
                                    <textarea class="form-control" id="address" name="address" rows="3" maxlength="500"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Phone Number
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Member Since
                                    </label>
                                    <p class="form-control-plaintext"><?php echo isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="col-lg-4 mb-4">
                    <div class="card password-card animate__animated animate__zoomIn animate__delay-2s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-warning w-100">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Account Statistics -->
                    <div class="card stats-card animate__animated animate__zoomIn animate__delay-3s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Account Stats</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get order count
                            $stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
                            $stmt->bind_param("i", $userId);
                            $stmt->execute();
                            $orderCount = $stmt->get_result()->fetch_assoc()['order_count'] ?? 0;
                            $stmt->close();
                            ?>
                            
                            <div class="stat-item">
                                <i class="fas fa-shopping-bag text-primary"></i>
                                <div>
                                    <strong><?php echo $orderCount; ?></strong>
                                    <small class="text-muted">Total Orders</small>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <i class="fas fa-user text-success"></i>
                                <div>
                                    <strong><?php echo htmlspecialchars($user['role'] ?? 'user'); ?></strong>
                                    <small class="text-muted">Account Type</small>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <i class="fas fa-calendar text-info"></i>
                                <div>
                                    <strong><?php echo isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'N/A'; ?></strong>
                                    <small class="text-muted">Joined</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center mt-5 animate__animated animate__fadeIn">
                <i class="fas fa-exclamation-triangle fa-5x text-muted mb-3 animate__animated animate__bounceIn"></i>
                <h3>Profile not found</h3>
                <p class="text-muted">Unable to load your profile information.</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Go Home
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            var newPassword = document.getElementById('new_password').value;
            var confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
