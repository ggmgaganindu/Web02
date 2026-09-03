<?php
require_once 'config/db.php';
session_start();

// Ensure student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ADD PRODUCT / VIDEO PACK
    if (isset($_POST['add_product'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $department = $_POST['department'] ?? 'General';
        $university = $_POST['university'] ?? 'General';
        $video_url = trim($_POST['video_url'] ?? '');
        $image_name = '';

        if (empty($title) || empty($description) || $price <= 0 || $category_id <= 0) {
            $error = 'Please fill in all required fields with valid data.';
        } else {
            // Process Image Upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_name = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($file_ext, $allowed_exts)) {
                    $new_filename = uniqid('prod_', true) . '.' . $file_ext;
                    $upload_dir = 'assets/uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                        $image_name = $new_filename;
                    } else {
                        $error = 'Failed to upload image.';
                    }
                } else {
                    $error = 'Invalid image file type. Allowed: jpg, jpeg, png, webp.';
                }
            }

            if (empty($error)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO products (seller_id, category_id, department, university, title, description, price, image_path, video_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->execute([$user_id, $category_id, $department, $university, $title, $description, $price, $image_name ? $image_name : null, $video_url ? $video_url : null]);
                    $success = 'Listing added successfully! It is pending admin approval.';
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }

    // 2. EDIT PRODUCT / VIDEO PACK
    if (isset($_POST['edit_product'])) {
        $product_id = intval($_POST['product_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $department = $_POST['department'] ?? 'General';
        $university = $_POST['university'] ?? 'General';
        $video_url = trim($_POST['video_url'] ?? '');

        if ($product_id <= 0 || empty($title) || empty($description) || $price <= 0 || $category_id <= 0) {
            $error = 'Please fill in all fields with valid data.';
        } else {
            try {
                // Verify ownership
                $chk_stmt = $pdo->prepare("SELECT seller_id, image_path FROM products WHERE id = ?");
                $chk_stmt->execute([$product_id]);
                $prod = $chk_stmt->fetch();

                if ($prod && $prod['seller_id'] == $user_id) {
                    $image_name = $prod['image_path'];

                    // Check if new image uploaded
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['image']['tmp_name'];
                        $file_name = $_FILES['image']['name'];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

                        if (in_array($file_ext, $allowed_exts)) {
                            $new_filename = uniqid('prod_', true) . '.' . $file_ext;
                            $upload_dir = 'assets/uploads/';
                            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                                // Delete old image
                                if ($image_name && file_exists($upload_dir . $image_name)) {
                                    unlink($upload_dir . $image_name);
                                }
                                $image_name = $new_filename;
                            }
                        } else {
                            $error = 'Invalid image file type.';
                        }
                    }

                    if (empty($error)) {
                        $stmt = $pdo->prepare("UPDATE products SET category_id = ?, department = ?, university = ?, title = ?, description = ?, price = ?, image_path = ?, video_url = ?, status = 'pending' WHERE id = ?");
                        $stmt->execute([$category_id, $department, $university, $title, $description, $price, $image_name, $video_url ? $video_url : null, $product_id]);
                        $success = 'Listing updated and sent for approval.';
                    }
                } else {
                    $error = 'Access denied or product not found.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// 3. DELETE PRODUCT
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    try {
        $chk_stmt = $pdo->prepare("SELECT seller_id, image_path FROM products WHERE id = ?");
        $chk_stmt->execute([$product_id]);
        $prod = $chk_stmt->fetch();

        if ($prod && $prod['seller_id'] == $user_id) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);

            if ($prod['image_path'] && file_exists('assets/uploads/' . $prod['image_path'])) {
                unlink('assets/uploads/' . $prod['image_path']);
            }
            $success = 'Product deleted successfully.';
        } else {
            $error = 'Access denied or product not found.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Fetch categories for forms
try {
    $categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $categories_stmt->fetchAll();

    // Fetch user listings
    $listings_stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.seller_id = ?
        ORDER BY p.created_at DESC
    ");
    $listings_stmt->execute([$user_id]);
    $my_listings = $listings_stmt->fetchAll();

    // Fetch Sales history
    $sales_stmt = $pdo->prepare("
        SELECT oi.*, p.title, o.buyer_id, u.username as buyer_name, o.created_at
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        JOIN users u ON o.buyer_id = u.id
        WHERE p.seller_id = ?
        ORDER BY o.created_at DESC
    ");
    $sales_stmt->execute([$user_id]);
    $sales = $sales_stmt->fetchAll();

    // Fetch Purchases history
    $purchases_stmt = $pdo->prepare("
        SELECT o.id as order_id, o.total_amount, o.payment_method, o.created_at, 
               GROUP_CONCAT(p.title SEPARATOR ', ') as item_details,
               MAX(p.id) as first_product_id,
               MAX(p.category_id) as category_id
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.buyer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $purchases_stmt->execute([$user_id]);
    $purchases = $purchases_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container dashboard-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <h3 class="sidebar-title">Student Account</h3>
        <ul class="sidebar-menu">
            <li><a href="#" class="sidebar-link active" id="link-listings">📦 My Listings (<?php echo count($my_listings); ?>)</a></li>
            <li><a href="#" class="sidebar-link" id="link-sales">📈 Sales History (<?php echo count($sales); ?>)</a></li>
            <li><a href="#" class="sidebar-link" id="link-purchases">🛍️ Purchase History (<?php echo count($purchases); ?>)</a></li>
        </ul>
    </aside>

    <!-- Content Panel -->
    <div class="dashboard-content">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- SECTION: MY LISTINGS -->
        <div id="section-listings" class="db-section">
            <div class="dashboard-header">
                <div>
                    <h1 style="font-weight: 800; font-size: 1.8rem; color: #0f172a;">My Products & Study Video Packs</h1>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">Manage your active products, textbooks, and video course packs.</p>
                </div>
                <button class="btn btn-primary" data-modal="add-modal">+ Add Product / Video Pack</button>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Category</th>
                            <th>Department & Stream</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_listings)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    You have not added any products or Study Video Packs yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($my_listings as $list): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($list['title']); ?></strong>
                                        <?php if ($list['video_url']): ?>
                                            <span style="font-size: 0.75rem; color: var(--danger); font-weight: 700; display: block;">🎥 Has Video Preview</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-approved">
                                            <?php echo htmlspecialchars($list['category_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                            <span class="badge badge-dept" style="font-size: 0.7rem;"><?php echo htmlspecialchars($list['department']); ?></span>
                                            <span class="badge badge-uni" style="font-size: 0.7rem;"><?php echo htmlspecialchars($list['university']); ?></span>
                                        </div>
                                    </td>
                                    <td><strong>LKR <?php echo number_format($list['price'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo $list['status']; ?>">
                                            <?php echo $list['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;"
                                                    data-modal="edit-modal"
                                                    data-id="<?php echo $list['id']; ?>"
                                                    data-title="<?php echo htmlspecialchars($list['title']); ?>"
                                                    data-description="<?php echo htmlspecialchars($list['description']); ?>"
                                                    data-price="<?php echo $list['price']; ?>"
                                                    data-category="<?php echo $list['category_id']; ?>"
                                                    data-department="<?php echo htmlspecialchars($list['department']); ?>"
                                                    data-university="<?php echo htmlspecialchars($list['university']); ?>"
                                                    data-video="<?php echo htmlspecialchars($list['video_url'] ?? ''); ?>">
                                                Edit
                                            </button>
                                            <a href="dashboard.php?action=delete&id=<?php echo $list['id']; ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem;" onclick="return confirm('Are you sure you want to delete this listing?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECTION: SALES HISTORY -->
        <div id="section-sales" class="db-section" style="display: none;">
            <h1 style="font-weight: 800; font-size: 1.8rem; color: #0f172a; margin-bottom: 5px;">📈 Sales History</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 24px;">Orders placed by other students for your items.</p>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Buyer</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    No sales transactions recorded yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td>#NSBM-<?php echo str_pad($sale['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><strong><?php echo htmlspecialchars($sale['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($sale['buyer_name']); ?></td>
                                    <td><?php echo $sale['quantity']; ?></td>
                                    <td><strong>LKR <?php echo number_format($sale['price'] * $sale['quantity'], 2); ?></strong></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($sale['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECTION: PURCHASE HISTORY -->
        <div id="section-purchases" class="db-section" style="display: none;">
            <h1 style="font-weight: 800; font-size: 1.8rem; color: #0f172a; margin-bottom: 5px;">🛍️ Purchase History</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 24px;">Your mock checkouts and simulated orders.</p>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Items Purchased</th>
                            <th>Payment Option</th>
                            <th>Total Spent</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($purchases)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    You have not simulated any purchases yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($purchases as $pur): ?>
                                <tr>
                                    <td>#NSBM-<?php echo str_pad($pur['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><strong><?php echo htmlspecialchars($pur['item_details']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-approved" style="font-size: 0.72rem;">
                                            <?php 
                                            $pm = $pur['payment_method'] ?? 'card';
                                            if ($pm === 'card') echo '💳 Card';
                                            elseif ($pm === 'bank_transfer') echo '🏦 Bank Transfer';
                                            else echo '💵 Cash on Campus';
                                            ?>
                                        </span>
                                    </td>
                                    <td><strong>LKR <?php echo number_format($pur['total_amount'], 2); ?></strong></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($pur['created_at'])); ?></td>
                                    <td>
                                        <?php if ($pur['category_id'] == 5 || strpos(strtolower($pur['item_details']), 'video') !== false): ?>
                                            <a href="watch.php?id=<?php echo $pur['first_product_id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.78rem; background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);">
                                                ▶️ Watch Online
                                            </a>
                                        <?php else: ?>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);">Standard Order</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: ADD PRODUCT / VIDEO PACK -->
<div class="modal" id="add-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-weight: 800; font-family: 'Space Grotesk', sans-serif; color: var(--text-main);">Add Product or Study Video Pack</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Title / Name</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Python OOP Video Pack / Math Textbook" required>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Price (LKR)</label>
                    <input type="number" name="price" step="0.01" class="form-control" placeholder="e.g. 1500.00" required>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Department / Faculty</label>
                    <select name="department" class="form-control" required>
                        <option value="Computing">💻 Computing</option>
                        <option value="Engineering">⚙️ Engineering</option>
                        <option value="Business">💼 Business</option>
                        <option value="General" selected>General / All</option>
                    </select>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label class="form-label">University Stream</label>
                    <select name="university" class="form-control" required>
                        <option value="Plymouth">🇬🇧 Plymouth University</option>
                        <option value="UGC/VU">🎓 UGC / VU</option>
                        <option value="General" selected>General / All</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Video Preview URL (For Study Video Packs)</label>
                <input type="url" name="video_url" class="form-control" placeholder="e.g. https://www.youtube.com/embed/gfkTfcpWqAY">
                <span style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-top: 4px;">Optional embed/preview link for video lectures or demo clips.</span>
            </div>

            <div class="form-group">
                <label class="form-label">Description & Course Details</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Detail course modules, lecture hours, or physical item condition..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Upload Image / Thumbnail</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <span style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-top: 4px;">Max size: 2MB. Format: JPG, PNG, WEBP.</span>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                <button type="submit" name="add_product" class="btn btn-primary">Submit Listing</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT PRODUCT / VIDEO PACK -->
<div class="modal" id="edit-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-weight: 800; font-family: 'Space Grotesk', sans-serif; color: var(--text-main);">Edit Listing</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="">
            
            <div class="form-group">
                <label class="form-label">Title / Name</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Price (LKR)</label>
                    <input type="number" name="price" step="0.01" class="form-control" required>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Department / Faculty</label>
                    <select name="department" class="form-control" required>
                        <option value="Computing">💻 Computing</option>
                        <option value="Engineering">⚙️ Engineering</option>
                        <option value="Business">💼 Business</option>
                        <option value="General">General / All</option>
                    </select>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label class="form-label">University Stream</label>
                    <select name="university" class="form-control" required>
                        <option value="Plymouth">🇬🇧 Plymouth University</option>
                        <option value="UGC/VU">🎓 UGC / VU</option>
                        <option value="General">General / All</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Video Preview URL</label>
                <input type="url" name="video_url" class="form-control" placeholder="e.g. https://www.youtube.com/embed/gfkTfcpWqAY">
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Upload New Image (Optional)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <span style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-top: 4px;">Leave blank to keep existing image.</span>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                <button type="submit" name="edit_product" class="btn btn-primary">Update Listing</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Tab switching logic
    const linkListings = document.getElementById('link-listings');
    const linkSales = document.getElementById('link-sales');
    const linkPurchases = document.getElementById('link-purchases');

    const sectionListings = document.getElementById('section-listings');
    const sectionSales = document.getElementById('section-sales');
    const sectionPurchases = document.getElementById('section-purchases');

    const links = [linkListings, linkSales, linkPurchases];
    const sections = [sectionListings, sectionSales, sectionPurchases];

    const activateTab = (activeLink, activeSection) => {
        links.forEach(l => l.classList.remove('active'));
        sections.forEach(s => s.style.display = 'none');

        activeLink.classList.add('active');
        activeSection.style.display = 'block';
    };

    linkListings.addEventListener('click', (e) => { e.preventDefault(); activateTab(linkListings, sectionListings); });
    linkSales.addEventListener('click', (e) => { e.preventDefault(); activateTab(linkSales, sectionSales); });
    linkPurchases.addEventListener('click', (e) => { e.preventDefault(); activateTab(linkPurchases, sectionPurchases); });
});
</script>

<?php include 'includes/footer.php'; ?>
