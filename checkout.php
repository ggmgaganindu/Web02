<?php
require_once 'config/db.php';
session_start();

// Ensure user is logged in and cart is not empty
if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$payment_method = $_POST['payment_method'] ?? 'card';
$delivery_location = trim($_POST['delivery_location'] ?? 'Faculty Lounge');
$contact_phone = trim($_POST['contact_phone'] ?? '');

$order_id = 0;
$purchased_items = [];
$total_amount = 0.00;

try {
    // 1. Verify user exists in database to prevent Foreign Key constraint failure
    $chk_user = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $chk_user->execute([$user_id]);
    $valid_user = $chk_user->fetch();

    if (!$valid_user) {
        // Fallback to first active student or redirect to login if session user is stale
        $default_user = $pdo->query("SELECT id, username FROM users WHERE role = 'student' AND status = 'active' ORDER BY id ASC LIMIT 1")->fetch();
        if ($default_user) {
            $user_id = $default_user['id'];
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $default_user['username'];
        } else {
            unset($_SESSION['user_id']);
            header('Location: login.php');
            exit;
        }
    }

    $pdo->beginTransaction();

    // Fetch items details
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($_SESSION['cart']));
    $products = $stmt->fetchAll();

    foreach ($products as $p) {
        $qty = $_SESSION['cart'][$p['id']];
        $subtotal = $p['price'] * $qty;
        $total_amount += $subtotal;
    }

    // Insert order into orders table
    $ins_order = $pdo->prepare("
        INSERT INTO orders (buyer_id, total_amount, payment_method, payment_status, delivery_location, contact_phone) 
        VALUES (?, ?, ?, 'completed', ?, ?)
    ");
    $ins_order->execute([$user_id, $total_amount, $payment_method, $delivery_location, $contact_phone]);
    $order_id = $pdo->lastInsertId();

    // Insert order items
    $ins_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)");
    foreach ($products as $p) {
        $qty = $_SESSION['cart'][$p['id']];
        $ins_item->execute([$order_id, $p['id'], $p['price'], $qty]);
        $p['qty'] = $qty;
        $purchased_items[] = $p;
    }

    $pdo->commit();

    // Clear cart session
    $_SESSION['cart'] = [];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Checkout error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container" style="padding: 50px 24px; min-height: 70vh;">
    <!-- Invoice Header -->
    <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 40px; box-shadow: var(--shadow-sm); max-width: 800px; margin: 0 auto;">
        <div style="text-align: center; border-bottom: 1px solid var(--border); padding-bottom: 24px; margin-bottom: 24px;">
            <div style="font-size: 3.5rem; margin-bottom: 10px;">🎉</div>
            <span class="badge badge-approved" style="font-size: 0.85rem; padding: 6px 14px; margin-bottom: 10px;">
                ✓ Simulated Purchase Successful
            </span>
            <h1 style="font-size: 2rem; font-weight: 800; font-family: 'Space Grotesk', sans-serif; color: var(--text-main); margin-top: 6px;">
                Order Receipt #NSBM-<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?>
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 4px;">
                Thank you for supporting student entrepreneurs at NSBM Green University!
            </p>
        </div>

        <!-- Payment Method & Delivery Info -->
        <div style="background: var(--bg-main); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 20px; margin-bottom: 24px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 2px;">PAYMENT METHOD</span>
                <strong style="font-size: 1rem; color: var(--primary-dark);">
                    <?php 
                    if ($payment_method === 'card') echo '💳 Online Credit / Debit Card (Paid)';
                    elseif ($payment_method === 'bank_transfer') echo '🏦 Bank Direct Transfer (Confirmed)';
                    else echo '💵 Cash on Campus / Pay on Delivery';
                    ?>
                </strong>
            </div>

            <div>
                <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 2px;">DELIVERY / LOCATION</span>
                <strong style="font-size: 0.95rem; color: var(--text-main);"><?php echo htmlspecialchars($delivery_location); ?></strong>
            </div>

            <div>
                <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 2px;">CONTACT PHONE</span>
                <strong style="font-size: 0.95rem; color: var(--text-main);"><?php echo htmlspecialchars($contact_phone); ?></strong>
            </div>
        </div>

        <!-- Purchased Items List -->
        <h3 style="font-size: 1.1rem; font-weight: 800; font-family: 'Space Grotesk', sans-serif; color: var(--text-main); margin-bottom: 14px;">Purchased Items & Access Links</h3>
        <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 30px;">
            <?php foreach ($purchased_items as $item): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: white;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <img src="assets/uploads/<?php echo $item['image_path'] ? $item['image_path'] : 'placeholder.png'; ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px;">
                        <div>
                            <strong style="font-size: 0.95rem; color: var(--text-main); display: block;"><?php echo htmlspecialchars($item['title']); ?></strong>
                            <small style="color: var(--text-muted); font-weight: 600;">Qty: <?php echo $item['qty']; ?> × LKR <?php echo number_format($item['price'], 2); ?></small>
                        </div>
                    </div>

                    <div>
                        <!-- Instant Watch Online Button for Video Packs -->
                        <?php if ($item['category_id'] == 5 || !empty($item['video_url'])): ?>
                            <a href="watch.php?id=<?php echo $item['id']; ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);">
                                ▶️ Watch Online Stream
                            </a>
                        <?php else: ?>
                            <span style="font-size: 0.95rem; font-weight: 800; color: var(--primary-dark);">LKR <?php echo number_format($item['price'] * $item['qty'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 20px;">
            <a href="index.php" class="btn btn-secondary">← Back to Marketplace</a>
            <a href="dashboard.php" class="btn btn-outline">Go to My Dashboard</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
