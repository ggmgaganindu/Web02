<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['user_role'] ?? '';
$username = $_SESSION['username'] ?? '';

// Cart items count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoMart - Student Marketplace</title>
    <!-- Fonts -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (in_array($current_page, ['dashboard.php', 'users.php', 'products.php', 'categories.php', 'index.php']) && (isset($_GET['admin']) || strpos($_SERVER['PHP_SELF'], '/admin/') !== false)): ?>
        <link rel="stylesheet" href="/assets/css/dashboard.css">
    <?php endif; ?>
    <?php if ($current_page === 'dashboard.php'): ?>
        <link rel="stylesheet" href="/assets/css/dashboard.css">
    <?php endif; ?>
    <?php if ($current_page === 'login.php'): ?>
        <link rel="stylesheet" href="/assets/css/auth.css">
    <?php endif; ?>
</head>
<body>
    <header>
        <div class="container navbar">
            <a href="/index.php" class="logo">
                <div class="logo-icon">E</div>
                <span>EcoMart</span>
            </a>
            
            <ul class="nav-links">
                <li><a href="/index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Marketplace</a></li>
                <?php if ($is_logged_in): ?>
                    <?php if ($user_role === 'admin'): ?>
                        <li><a href="/admin/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : ''; ?>">Admin Panel</a></li>
                    <?php else: ?>
                        <li><a href="/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">My Dashboard</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <div class="nav-actions">
                <a href="/cart.php" class="btn btn-secondary" style="position: relative; padding: 10px 15px;">
                    🛒 Cart
                    <?php if ($cart_count > 0): ?>
                        <span style="position: absolute; top: -5px; right: -5px; background-color: var(--accent); color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <?php if ($is_logged_in): ?>
                    <span style="font-weight: 600; color: var(--text-main);">Hi, <?php echo htmlspecialchars($username); ?></span>
                    <a href="/logout.php" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);">Logout</a>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-primary">Login / Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main style="flex: 1;">
