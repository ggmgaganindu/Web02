<?php
require_once '../config/db.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Process Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $target_id = intval($_GET['id']);
    
    // Prevent actions on self
    if ($target_id === $_SESSION['user_id']) {
        $error = 'You cannot modify your own administrator account.';
    } else {
        if ($_GET['action'] === 'toggle') {
            try {
                $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
                $stmt->execute([$target_id]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $new_status = $user['status'] === 'active' ? 'inactive' : 'active';
                    $up_stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                    $up_stmt->execute([$new_status, $target_id]);
                    $success = 'User status updated successfully.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
        
        if ($_GET['action'] === 'delete') {
            try {
                // Delete user (database schema has ON DELETE CASCADE for foreign keys)
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$target_id]);
                $success = 'User account and all associated listings/orders deleted.';
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all student users
try {
    $users_stmt = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC");
    $users = $users_stmt->fetchAll();
    
    $prod_pending = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include '../includes/header.php';
?>

<div class="container dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h3 class="sidebar-title">Admin Controls</h3>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="sidebar-link">📊 Dashboard Home</a></li>
            <li><a href="users.php" class="sidebar-link active">👥 Manage Users</a></li>
            <li><a href="products.php" class="sidebar-link"> Moderation Queue (<?php echo $prod_pending; ?>)</a></li>
            <li><a href="categories.php" class="sidebar-link">📁 Categories CRUD</a></li>
        </ul>
    </aside>

    <!-- Main Content Panel -->
    <div class="dashboard-content">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-header">
            <div>
                <h1 style="font-weight: 800; font-size: 1.8rem; color: #0f172a;">Manage Users</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">View registered student accounts, toggle access status, or delete accounts.</p>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No student users registered yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $usr): ?>
                            <tr>
                                <td><?php echo $usr['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($usr['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($usr['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $usr['status'] === 'active' ? 'approved' : 'rejected'; ?>">
                                        <?php echo $usr['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($usr['created_at'])); ?></td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="users.php?action=toggle&id=<?php echo $usr['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                                            <?php echo $usr['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                        <a href="users.php?action=delete&id=<?php echo $usr['id']; ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem;" onclick="return confirm('Are you sure you want to delete this user? This will remove all their listings and purchases!')">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
