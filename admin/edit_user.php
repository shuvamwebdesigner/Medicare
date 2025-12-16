<?php
include '../config/db.php';
include '../includes/auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$error = '';
$success = '';

// Fetch user data
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        $error = "User not found.";
    }
} else {
    $error = "Invalid user ID.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    
    if (empty($name) || empty($email) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email is already taken by another user.";
        } else {
            // Update user
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $role, $userId);
            
            if ($stmt->execute()) {
                $success = "User updated successfully.";
                // Refresh user data
                $user['name'] = $name;
                $user['email'] = $email;
                $user['role'] = $role;
            } else {
                $error = "Error updating user: " . $conn->error;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/edit_user.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container dashboard-container">
        <div class="welcome-message animate__animated animate__fadeInUp animate__delay-1s">
            <h1><i class="fas fa-user-edit me-3"></i>Edit User</h1>
            <p>Update user information and permissions</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn animate__delay-2s">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success animate__animated animate__fadeIn animate__delay-2s">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card edit-card animate__animated animate__zoomIn animate__delay-3s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>User Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="user-avatar text-center mb-4">
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                                <h4 class="mt-3"><?php echo htmlspecialchars($user['name']); ?></h4>
                                <p class="text-muted">Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-signature me-1"></i>Name
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>Email
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-at"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="role" class="form-label">
                                        <i class="fas fa-shield-alt me-1"></i>Role
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-crown"></i></span>
                                        <select class="form-control" id="role" name="role" required>
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>👤 User</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>👑 Admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="manage_users.php" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Users
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Update User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center mt-5 animate__animated animate__fadeIn animate__delay-2s">
                <i class="fas fa-exclamation-triangle fa-5x text-muted mb-3 animate__animated animate__bounceIn"></i>
                <h3>User not found</h3>
                <p class="text-muted">The requested user could not be found.</p>
                <a href="manage_users.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>Back to Users
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>