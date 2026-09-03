<?php
require_once 'config/db.php';
session_start();

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions (POST or GET)
$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$product_id = intval($_POST['product_id'] ?? ($_GET['id'] ?? ($_GET['product_id'] ?? 0)));
$quantity = intval($_POST['quantity'] ?? 1);

if ($action === 'add' && $product_id > 0) {
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
    header('Location: cart.php');
    exit;
}

if ($action === 'update' && $product_id > 0) {
    if ($quantity > 0) {
        $_SESSION['cart'][$product_id] = $quantity;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
    header('Location: cart.php');
    exit;
}

if ($action === 'remove' && $product_id > 0) {
    unset($_SESSION['cart'][$product_id]);
    header('Location: cart.php');
    exit;
}

// Validate session user against database
if (isset($_SESSION['user_id'])) {
    try {
        $chk = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ? LIMIT 1");
        $chk->execute([$_SESSION['user_id']]);
        $u = $chk->fetch();
        if (!$u) {
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
            unset($_SESSION['user_role']);
        }
    } catch (PDOException $e) {
        // Silently handle DB check
    }
}

// Fetch products currently in the cart
$cart_products = [];
$total_amount = 0.00;

if (!empty($_SESSION['cart'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.username as seller_name 
            FROM products p 
            JOIN users u ON p.seller_id = u.id 
            WHERE p.id IN ($placeholders)
        ");
        $stmt->execute(array_keys($_SESSION['cart']));
        $db_products = $stmt->fetchAll();

        foreach ($db_products as $prod) {
            $qty = $_SESSION['cart'][$prod['id']];
            $subtotal = $prod['price'] * $qty;
            $total_amount += $subtotal;
            $prod['qty'] = $qty;
            $prod['subtotal'] = $subtotal;
            $cart_products[] = $prod;
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 40px 24px; min-height: 70vh;">
    <h1 style="font-weight: 800; font-size: 2.2rem; margin-bottom: 30px; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">
        🛒 Shopping Cart & Checkout
    </h1>

    <?php if (empty($cart_products)): ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border: 1px solid var(--border); border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
            <div style="font-size: 4rem; margin-bottom: 20px;">🛒</div>
            <h2 style="font-size: 1.5rem; color: var(--text-main); margin-bottom: 10px;">Your cart is empty</h2>
            <p style="color: var(--text-muted); margin-bottom: 24px;">Explore our marketplace to find great student products, services, and Study Video Packs.</p>
            <a href="index.php" class="btn btn-primary">Start Browsing</a>
        </div>
    <?php else: ?>
        <div style="display: flex; gap: 40px; flex-wrap: wrap;">
            <!-- Cart Items Table -->
            <div style="flex: 2; min-width: 320px;">
                <div class="table-container" style="margin-top: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product / Video Pack</th>
                                <th>Seller</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_products as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <img src="assets/uploads/<?php echo $item['image_path'] ? $item['image_path'] : 'placeholder.png'; ?>" class="product-thumb" alt="" style="width: 54px; height: 54px; min-width: 54px; max-width: 54px; max-height: 54px; object-fit: cover; border-radius: 8px; flex-shrink: 0;">
                                            <div>
                                                <strong style="display: block; color: var(--text-main);"><?php echo htmlspecialchars($item['title']); ?></strong>
                                                <?php if (!empty($item['video_url'])): ?>
                                                    <span style="font-size: 0.75rem; color: var(--primary-dark); font-weight: 700;">🎥 Includes Online Video Stream</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['seller_name']); ?></td>
                                    <td>LKR <?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <form action="cart.php" method="POST" style="display: flex; align-items: center; gap: 8px;">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="number" name="quantity" value="<?php echo $item['qty']; ?>" min="1" style="width: 60px; padding: 6px; border: 1px solid var(--border); border-radius: var(--radius-sm); text-align: center;" onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td><strong>LKR <?php echo number_format($item['subtotal'], 2); ?></strong></td>
                                    <td>
                                        <form action="cart.php" method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem;">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px; display: flex; justify-content: space-between;">
                    <a href="index.php" class="btn btn-secondary">← Continue Shopping</a>
                </div>
            </div>

            <!-- Order Summary & Choose Payment Method -->
            <div style="flex: 1; min-width: 320px;">
                <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 20px;">
                    <h3 style="font-size: 1.25rem; font-weight: 800; font-family: 'Space Grotesk', sans-serif; color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 5px;">Order Summary</h3>
                    
                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem; color: var(--text-muted);">
                        <span>Subtotal</span>
                        <span>LKR <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem; color: var(--text-muted);">
                        <span>Delivery / Video Access</span>
                        <span style="color: var(--success); font-weight: 600;">INSTANT / FREE</span>
                    </div>
                    
                    <div style="border-top: 1px solid var(--border); padding-top: 15px; display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 800; color: var(--text-main);">
                        <span>Total</span>
                        <span style="color: var(--primary-dark);">LKR <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Simulated Checkout with Choose Payment Method Options -->
                        <form action="checkout.php" method="POST" style="display: flex; flex-direction: column; gap: 16px; border-top: 1px solid var(--border); padding-top: 20px; margin-top: 10px;">
                            <h4 style="font-size: 1rem; font-weight: 800; font-family: 'Space Grotesk', sans-serif; color: var(--text-main);">💳 Choose Pay Option</h4>
                            
                            <!-- Payment Options Selection Cards -->
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                
                                <!-- Radio 1: Card -->
                                <label style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border: 1px solid var(--primary); border-radius: var(--radius-sm); background: rgba(0, 180, 216, 0.05); cursor: pointer;" id="pay-card-label">
                                    <input type="radio" name="payment_method" value="card" checked id="pay-card-radio">
                                    <div>
                                        <strong style="font-size: 0.92rem; color: var(--text-main); display: block;">💳 Online Credit / Debit Card</strong>
                                        <small style="font-size: 0.78rem; color: var(--text-muted);">Visa, MasterCard, AMEX (Instant Video Unlock)</small>
                                    </div>
                                </label>

                                <!-- Radio 2: Bank Transfer -->
                                <label style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-main); cursor: pointer;" id="pay-bank-label">
                                    <input type="radio" name="payment_method" value="bank_transfer" id="pay-bank-radio">
                                    <div>
                                        <strong style="font-size: 0.92rem; color: var(--text-main); display: block;">🏦 Bank Direct Transfer / QR</strong>
                                        <small style="font-size: 0.78rem; color: var(--text-muted);">BOC / Commercial Bank NSBM Account</small>
                                    </div>
                                </label>

                                <!-- Radio 3: Cash on Campus -->
                                <label style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-main); cursor: pointer;" id="pay-cash-label">
                                    <input type="radio" name="payment_method" value="cash_on_campus" id="pay-cash-radio">
                                    <div>
                                        <strong style="font-size: 0.92rem; color: var(--text-main); display: block;">💵 Cash on Campus / Meetup</strong>
                                        <small style="font-size: 0.78rem; color: var(--text-muted);">Meet seller on campus / Student Union</small>
                                    </div>
                                </label>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 0.82rem;">Delivery Location / Notes</label>
                                <input type="text" name="delivery_location" class="form-control" placeholder="e.g. Faculty of Computing / Online Access" value="Online Access / Faculty Lounge" required style="padding: 10px; font-size: 0.85rem;">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 0.82rem;">Contact Phone Number</label>
                                <input type="text" name="contact_phone" class="form-control" placeholder="e.g. 0771234567" required style="padding: 10px; font-size: 0.85rem;">
                            </div>

                            <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; margin-top: 10px; font-size: 1rem;">
                                Confirm & Complete Purchase 🚀
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="border-top: 1px solid var(--border); padding-top: 20px; margin-top: 10px; text-align: center;">
                            <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 15px;">Please log in to choose payment options and complete purchase.</p>
                            <a href="login.php" class="btn btn-primary" style="width: 100%;">Login to Purchase</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="payment_method"]');
    radios.forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('#pay-card-label, #pay-bank-label, #pay-cash-label').forEach(label => {
                label.style.borderColor = 'var(--border)';
                label.style.background = 'var(--bg-main)';
            });
            const selectedLabel = radio.closest('label');
            if (selectedLabel) {
                selectedLabel.style.borderColor = 'var(--primary)';
                selectedLabel.style.background = 'rgba(0, 180, 216, 0.05)';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
