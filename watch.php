<?php
require_once 'config/db.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$user_role = $_SESSION['user_role'] ?? '';
$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

$is_authorized = false;
$product = null;

try {
    // Fetch product details
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, u.username as seller_name, u.email as seller_email 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.seller_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        die("Lesson Pack not found.");
    }

    // IDOR Protection Check:
    // 1. Admin user has full access
    if ($user_role === 'admin') {
        $is_authorized = true;
    }
    // 2. Seller who created the product has access
    elseif ($product['seller_id'] == $user_id) {
        $is_authorized = true;
    }
    // 3. Check if user has purchased this product in orders table
    else {
        $order_stmt = $pdo->prepare("
            SELECT oi.id 
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.buyer_id = ? AND oi.product_id = ? AND o.payment_status = 'completed'
            LIMIT 1
        ");
        $order_stmt->execute([$user_id, $product_id]);
        if ($order_stmt->fetch()) {
            $is_authorized = true;
        }
    }

    // Decode lesson playlist JSON
    $playlist = [];
    if (!empty($product['lesson_playlist'])) {
        $playlist = json_decode($product['lesson_playlist'], true) ?: [];
    }

    if (empty($playlist)) {
        $playlist = [
            [
                'title' => 'Module 1: Full Course Overview & Foundations',
                'url' => $product['video_url'] ?: 'https://www.youtube.com/embed/gfkTfcpWqAY',
                'duration' => '45 mins'
            ],
            [
                'title' => 'Module 2: Practical Lab Exercises & Worked Examples',
                'url' => 'https://www.youtube.com/embed/HXV3zeQKqGY',
                'duration' => '1 hr 10 mins'
            ],
            [
                'title' => 'Module 3: Exam Revision & Past Paper Solutions',
                'url' => 'https://www.youtube.com/embed/1v0mK5Z4_5M',
                'duration' => '55 mins'
            ]
        ];
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container" style="padding: 40px 24px 80px;">
    <!-- Navigation Back Link -->
    <div style="margin-bottom: 24px;">
        <a href="dashboard.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.88rem;">← Back to My Dashboard</a>
    </div>

    <?php if (!$is_authorized): ?>
        <!-- IDOR Access Denied Screen -->
        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 50px 30px; text-align: center; max-width: 650px; margin: 40px auto; box-shadow: var(--shadow-sm);">
            <div style="font-size: 4rem; margin-bottom: 16px;">🔒</div>
            <span class="badge badge-rejected" style="font-size: 0.82rem; padding: 6px 14px; margin-bottom: 12px;">
                403 Access Restricted (IDOR Protection)
            </span>
            <h2 style="font-size: 1.8rem; font-weight: 800; font-family: 'Space Grotesk', sans-serif; color: var(--text-main); margin-top: 8px; margin-bottom: 12px;">
                Purchase Required to Access Video Stream
            </h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 24px;">
                You do not have active purchase authorization for <strong><?php echo htmlspecialchars($product['title']); ?></strong>. Please purchase this Study Video Pack in the marketplace to unlock full online streaming access.
            </p>

            <div style="display: flex; gap: 12px; justify-content: center;">
                <form action="cart.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="action" value="add">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 0.95rem;">
                        🛒 Buy Now (LKR <?php echo number_format($product['price'], 2); ?>)
                    </button>
                </form>
                <a href="index.php" class="btn btn-secondary" style="padding: 12px 24px;">Browse Marketplace</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Authorized Video Learning Portal -->
        <div style="background: linear-gradient(135deg, #0f172a 0%, #0077b6 100%); border-radius: var(--radius-md); padding: 28px 32px; color: white; margin-bottom: 30px; box-shadow: var(--shadow-md);">
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                <span class="badge badge-approved" style="background: rgba(255,255,255,0.2); color: white;">🎥 Purchased Study Video Pack</span>
                <?php if ($product['department'] !== 'General'): ?>
                    <span class="badge badge-dept" style="background: rgba(255,255,255,0.15); color: white; border-color: rgba(255,255,255,0.3);">
                        💻 <?php echo htmlspecialchars($product['department']); ?> Department
                    </span>
                <?php endif; ?>
                <?php if ($product['university'] !== 'General'): ?>
                    <span class="badge badge-uni" style="background: rgba(255,255,255,0.15); color: white; border-color: rgba(255,255,255,0.3);">
                        🎓 <?php echo htmlspecialchars($product['university']); ?> Stream
                    </span>
                <?php endif; ?>
            </div>

            <h1 style="font-size: 2.2rem; font-weight: 800; font-family: 'Space Grotesk', sans-serif; line-height: 1.25;">
                <?php echo htmlspecialchars($product['title']); ?>
            </h1>
            <p style="color: #cbd5e1; font-size: 1rem; margin-top: 8px; max-width: 750px;">
                <?php echo htmlspecialchars($product['description']); ?>
            </p>
        </div>

        <!-- Video Learning Player Grid -->
        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
            
            <!-- Video Stream Player Screen -->
            <div style="flex: 2; min-width: 320px;">
                <div style="background: #0f172a; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow-lg);">
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                        <iframe id="main-video-player" src="<?php echo htmlspecialchars($playlist[0]['url']); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;" allowfullscreen></iframe>
                    </div>

                    <div style="padding: 24px; color: white; background: #090d16;">
                        <span style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: #38bdf8; font-weight: 800;">NOW STREAMING</span>
                        <h3 id="current-lesson-title" style="font-size: 1.4rem; font-weight: 800; font-family: 'Space Grotesk', sans-serif; margin-top: 4px;">
                            <?php echo htmlspecialchars($playlist[0]['title']); ?>
                        </h3>
                        <div style="display: flex; gap: 16px; font-size: 0.85rem; color: #94a3b8; margin-top: 8px;">
                            <span>⏱️ Duration: <strong id="current-lesson-duration" style="color: white;"><?php echo htmlspecialchars($playlist[0]['duration']); ?></strong></span>
                            <span>Instructor: <strong style="color: white;"><?php echo htmlspecialchars($product['seller_name']); ?></strong></span>
                        </div>
                    </div>
                </div>

                <!-- Lesson Notes & Resources -->
                <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 24px; margin-top: 24px; box-shadow: var(--shadow-sm);">
                    <h4 style="font-size: 1.1rem; font-weight: 800; font-family: 'Space Grotesk', sans-serif; color: var(--text-main); margin-bottom: 10px;">
                        📝 Course Notes & Attachments
                    </h4>
                    <p style="font-size: 0.92rem; color: var(--text-muted); line-height: 1.6;">
                        Welcome to this Study Video Pack! Use the lesson modules playlist on the right to navigate through individual topic walkthroughs, code samples, and past exam solution walkthroughs.
                    </p>
                    
                    <div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="mailto:<?php echo htmlspecialchars($product['seller_email']); ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem;">
                            ✉️ Contact Tutor (<?php echo htmlspecialchars($product['seller_name']); ?>)
                        </a>
                    </div>
                </div>
            </div>

            <!-- Lesson Modules Playlist Sidebar -->
            <div style="flex: 1; min-width: 280px;">
                <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 24px; box-shadow: var(--shadow-sm); height: fit-content;">
                    <h3 style="font-size: 1.25rem; font-weight: 800; font-family: 'Space Grotesk', sans-serif; color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 18px;">
                        📚 Course Playlist (<?php echo count($playlist); ?> Modules)
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($playlist as $index => $item): ?>
                            <div class="playlist-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 data-url="<?php echo htmlspecialchars($item['url']); ?>"
                                 data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                 data-duration="<?php echo htmlspecialchars($item['duration']); ?>"
                                 style="padding: 14px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border); cursor: pointer; transition: var(--transition); background: <?php echo $index === 0 ? '#f0f9ff' : 'var(--bg-main)'; ?>; border-color: <?php echo $index === 0 ? 'var(--primary)' : 'var(--border)'; ?>;">
                                
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                                    <strong style="font-size: 0.92rem; color: var(--text-main); line-height: 1.35;">
                                        ▶️ <?php echo htmlspecialchars($item['title']); ?>
                                    </strong>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600; margin-top: 6px;">
                                    ⏱️ <?php echo htmlspecialchars($item['duration']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const playlistItems = document.querySelectorAll('.playlist-item');
    const player = document.getElementById('main-video-player');
    const titleEl = document.getElementById('current-lesson-title');
    const durationEl = document.getElementById('current-lesson-duration');

    playlistItems.forEach(item => {
        item.addEventListener('click', () => {
            playlistItems.forEach(i => {
                i.style.background = 'var(--bg-main)';
                i.style.borderColor = 'var(--border)';
                i.classList.remove('active');
            });

            item.style.background = '#f0f9ff';
            item.style.borderColor = 'var(--primary)';
            item.classList.add('active');

            player.src = item.dataset.url;
            titleEl.textContent = item.dataset.title;
            durationEl.textContent = item.dataset.duration;
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
