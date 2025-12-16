<?php
include '../config/db.php';
include '../includes/auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        $success = "User deleted successfully.";
    } else {
        $error = "Error deleting user: " . $conn->error;
    }
}

// Fetch all users
$result = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
} else {
    $error = "Error fetching users: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/manage_users.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container dashboard-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1>Manage Users</h1>
            <p>View, edit, or delete user accounts</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success animate__animated animate__fadeIn"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($users as $user): ?>
                <div class="col-md-6 mb-4">
                    <div class="card user-card animate__animated animate__zoomIn">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-user-circle card-icon me-3"></i>
                                <div>
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                                    <p class="card-text mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                                    <small class="text-muted">Role: <?php echo htmlspecialchars($user['role']); ?></small>
                                </div>
                            </div>
                            <p class="card-text"><small class="text-muted">Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?></small></p>
                            <div class="d-flex justify-content-between">
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($users)): ?>
            <div class="text-center mt-5">
                <i class="fas fa-users fa-5x text-muted mb-3"></i>
                <h3>No users found</h3>
                <p class="text-muted">There are no users in the system yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>