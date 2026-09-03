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

// Handle Moderation actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'reject', 'delete'])) {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE products SET status = 'approved' WHERE id = ?");
                $stmt->execute([$product_id]);
                $success = 'Product listing approved and published.';
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE products SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$product_id]);
                $success = 'Product listing rejected.';
            } elseif ($action === 'delete') {
                // Fetch image to delete
                $img_stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
                $img_stmt->execute([$product_id]);
                $img = $img_stmt->fetchColumn();
                
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                
                if ($img && file_exists('../assets/uploads/' . $img)) {
                    unlink('../assets/uploads/' . $img);
                }
                $success = 'Listing deleted permanently.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch lists
try {
    // 1. Pending listings
    $pending_stmt = $pdo->query("
        SELECT p.*, c.name as category_name, u.username as seller_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.seller_id = u.id
        WHERE p.status = 'pending'
        ORDER BY p.created_at DESC
    ");
    $pending_products = $pending_stmt->fetchAll();
    $prod_pending = count($pending_products);

    // 2. All other listings (Approved/Rejected)
    $other_stmt = $pdo->query("
        SELECT p.*, c.name as category_name, u.username as seller_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.seller_id = u.id
        WHERE p.status != 'pending'
        ORDER BY p.created_at DESC
    ");
    $other_products = $other_stmt->fetchAll();

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
            <li><a href="users.php" class="sidebar-link">👥 Manage Users</a></li>
            <li><a href="products.php" class="sidebar-link active"> Moderation Queue (<?php echo $prod_pending; ?>)</a></li>
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

        <!-- SECTION 1: PENDING MODERATION -->
        <div class="dashboard-header">
            <div>
                <h1 style="font-weight: 800; font-size: 1.8rem; color: #0f172a;">Moderation Queue</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Review submissions and Study Video Packs from student entrepreneurs before they go live.</p>
            </div>
        </div>

        <div class="table-container" style="margin-bottom: 40px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item Details</th>
                        <th>Seller</th>
                        <th>Category & Department</th>
                        <th>Price</th>
                        <th>Date Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_products)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 35px;">
                                No pending listings in moderation queue.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pending_products as $prod): ?>
                            <tr>
                                <td>
                                    <div class="product-cell" style="align-items: flex-start;">
                                        <img src="../assets/uploads/<?php echo $prod['image_path'] ? $prod['image_path'] : 'placeholder.png'; ?>" class="product-thumb" alt="">
                                        <div>
                                            <strong style="color: var(--text-main); display: block;"><?php echo htmlspecialchars($prod['title']); ?></strong>
                                            <small style="color: var(--text-muted); display: block; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?php echo htmlspecialchars($prod['description']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($prod['seller_name']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prod['category_name']); ?></strong>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px;">
                                        <span class="badge badge-dept" style="font-size: 0.68rem;"><?php echo htmlspecialchars($prod['department']); ?></span>
                                        <span class="badge badge-uni" style="font-size: 0.68rem;"><?php echo htmlspecialchars($prod['university']); ?></span>
                                    </div>
                                </td>
                                <td><strong>LKR <?php echo number_format($prod['price'], 2); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($prod['created_at'])); ?></td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="products.php?action=approve&id=<?php echo $prod['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem; background: var(--success); box-shadow: none;">
                                            Approve
                                        </a>
                                        <a href="products.php?action=reject&id=<?php echo $prod['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; background: #fee2e2; color: #b91c1c;">
                                            Reject
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- SECTION 2: OTHER LISTINGS -->
        <h2 style="font-weight: 800; font-size: 1.4rem; color: #0f172a; margin-bottom: 15px;">All Other Listings</h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Seller</th>
                        <th>Category & Stream</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($other_products)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No listings have been processed yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($other_products as $prod): ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <img src="../assets/uploads/<?php echo $prod['image_path'] ? $prod['image_path'] : 'placeholder.png'; ?>" class="product-thumb" alt="">
                                        <strong style="color: var(--text-main);"><?php echo htmlspecialchars($prod['title']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($prod['seller_name']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prod['category_name']); ?></strong>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px;">
                                        <span class="badge badge-dept" style="font-size: 0.68rem;"><?php echo htmlspecialchars($prod['department']); ?></span>
                                        <span class="badge badge-uni" style="font-size: 0.68rem;"><?php echo htmlspecialchars($prod['university']); ?></span>
                                    </div>
                                </td>
                                <td>LKR <?php echo number_format($prod['price'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $prod['status']; ?>">
                                        <?php echo $prod['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <?php if ($prod['status'] === 'rejected'): ?>
                                            <a href="products.php?action=approve&id=<?php echo $prod['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                                                Approve
                                            </a>
                                        <?php else: ?>
                                            <a href="products.php?action=reject&id=<?php echo $prod['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; color: var(--danger);">
                                                Reject
                                            </a>
                                        <?php endif; ?>
                                        <a href="products.php?action=delete&id=<?php echo $prod['id']; ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem;" onclick="return confirm('Are you sure you want to delete this listing permanently?')">
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
