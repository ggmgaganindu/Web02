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

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ADD CATEGORY
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Category name cannot be empty.';
        } else {
            try {
                // Check duplicate
                $chk = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $chk->execute([$name]);
                if ($chk->fetch()) {
                    $error = 'Category name already exists.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    $stmt->execute([$name, $description]);
                    $success = 'Category added successfully.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
    
    // 2. EDIT CATEGORY
    if (isset($_POST['edit_category'])) {
        $category_id = intval($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($category_id <= 0 || empty($name)) {
            $error = 'Category ID and name are required.';
        } else {
            try {
                // Check duplicates (excluding self)
                $chk = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                $chk->execute([$name, $category_id]);
                if ($chk->fetch()) {
                    $error = 'Another category with this name already exists.';
                } else {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $category_id]);
                    $success = 'Category updated successfully.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// 3. DELETE CATEGORY
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $category_id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $success = 'Category deleted successfully (along with all products in this category).';
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Fetch all categories
try {
    $categories_stmt = $pdo->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id
        ORDER BY c.name ASC
    ");
    $categories = $categories_stmt->fetchAll();
    
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
            <li><a href="users.php" class="sidebar-link">👥 Manage Users</a></li>
            <li><a href="products.php" class="sidebar-link"> Moderation Queue (<?php echo $prod_pending; ?>)</a></li>
            <li><a href="categories.php" class="sidebar-link active">📁 Categories CRUD</a></li>
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
                <h1 style="font-weight: 800; font-size: 1.8rem; color: #0f172a;">Product Categories</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Manage the categories available for student listings.</p>
            </div>
            <button class="btn btn-primary" data-modal="add-cat-modal">+ Add Category</button>
        </div>

        <!-- Categories Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Product Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No categories defined. Please add one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td style="max-width: 300px; font-size: 0.9rem; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($cat['description']); ?>
                                </td>
                                <td><span style="font-weight: 600; color: var(--primary);"><?php echo $cat['product_count']; ?></span> items</td>
                                <td>
                                    <div class="actions-cell">
                                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;"
                                                data-modal="edit-cat-modal"
                                                data-id="<?php echo $cat['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($cat['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($cat['description']); ?>">
                                            Edit
                                        </button>
                                        <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem;" onclick="return confirm('Are you sure you want to delete this category? This will delete all products in this category!')">
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

<!-- MODAL: ADD CATEGORY -->
<div class="modal" id="add-cat-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-weight: 700; color: var(--text-main);">Add Category</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form action="categories.php" method="POST">
            <div class="form-group">
                <label class="form-label">Category Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Books & Stationary" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Brief description of items allowed in this category..."></textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT CATEGORY -->
<div class="modal" id="edit-cat-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-weight: 700; color: var(--text-main);">Edit Category</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form action="categories.php" method="POST">
            <input type="hidden" name="category_id" value="">
            
            <div class="form-group">
                <label class="form-label">Category Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                <button type="submit" name="edit_category" class="btn btn-primary">Update Category</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Override the generic modal trigger details for Category forms
    const categoryTriggers = document.querySelectorAll('[data-modal="edit-cat-modal"]');
    categoryTriggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
            const modal = document.getElementById('edit-cat-modal');
            if (modal) {
                modal.querySelector('[name="category_id"]').value = trigger.dataset.id;
                modal.querySelector('[name="name"]').value = trigger.dataset.title;
                modal.querySelector('[name="description"]').value = trigger.dataset.description;
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
