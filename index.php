<?php
require_once 'config/db.php';
session_start();

// Fetch categories for filtering
try {
    $categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $categories_stmt ? $categories_stmt->fetchAll() : [];
    
    // Fetch all approved products
    $products_stmt = $pdo->query("
        SELECT p.*, c.name as category_name, u.username as seller_name, u.email as seller_email 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.seller_id = u.id
        WHERE p.status = 'approved'
        ORDER BY p.created_at DESC
    ");
    $products = $products_stmt ? $products_stmt->fetchAll() : [];
} catch (PDOException $e) {
    $categories = [];
    $products = [];
}

include 'includes/header.php';
?>

<!-- Hero Section (Cinematic Mesh Gradient) -->
<section class="hero">
    <div class="container">
        <h1>EcoMart</h1>
        <p>Promote, discover, and trade products, services & <strong>Study Video Packs</strong> across NSBM faculties.</p>
        
        <!-- Search bar -->
        <div class="hero-search-box">
            <input type="text" id="search-input" class="hero-search-input" placeholder="Search for Study Video Packs, Python lectures, Engineering maths, textbooks...">
            <span style="position: absolute; left: 22px; top: 16px; font-size: 1.25rem; color: #94a3b8; pointer-events: none;">🔍</span>
        </div>
    </div>
</section>

<!-- Filters & Products Container -->
<section class="container" style="padding-bottom: 80px;">

    <!-- Primary Category Filter Tabs -->
    <div style="margin-top: 35px; text-align: center;">
        <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 8px;">Resource Categories</span>
        <div class="category-tabs" style="margin-top: 0;">
            <button class="btn category-btn active btn-primary" data-category="all" data-category-name="all">✨ All Resources</button>
            <?php foreach ($categories as $cat): ?>
                <button class="btn category-btn btn-secondary" data-category="<?php echo $cat['id']; ?>" data-category-name="<?php echo htmlspecialchars($cat['name']); ?>">
                    <?php if ($cat['name'] === 'Student Services'): ?>🎥 <?php endif; ?>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Secondary Faculty / Department & University Sub-Filter Box (Only displayed under Student Services) -->
    <div id="sub-filter-container" style="display: none; margin-top: 20px; text-align: center; background: white; padding: 20px 24px; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
        <div style="margin-bottom: 12px;">
            <span style="font-size: 0.85rem; font-weight: 800; color: var(--primary-dark); text-transform: uppercase; letter-spacing: 0.04em;">
                🎓 Filter Student Services & Study Video Packs by Faculty & Stream
            </span>
        </div>
        <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px;">
            
            <!-- Department Filter -->
            <div>
                <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Department / Faculty</span>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                    <button class="btn dept-btn active btn-primary" data-dept="all" style="padding: 6px 16px; font-size: 0.82rem;">All Departments</button>
                    <button class="btn dept-btn btn-secondary" data-dept="Computing" style="padding: 6px 16px; font-size: 0.82rem;">💻 Computing</button>
                    <button class="btn dept-btn btn-secondary" data-dept="Engineering" style="padding: 6px 16px; font-size: 0.82rem;">⚙️ Engineering</button>
                    <button class="btn dept-btn btn-secondary" data-dept="Business" style="padding: 6px 16px; font-size: 0.82rem;">💼 Business</button>
                </div>
            </div>

            <!-- University Stream Filter -->
            <div>
                <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">University Stream</span>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                    <button class="btn uni-btn active btn-primary" data-uni="all" style="padding: 6px 16px; font-size: 0.82rem;">All Streams</button>
                    <button class="btn uni-btn btn-secondary" data-uni="Plymouth" style="padding: 6px 16px; font-size: 0.82rem;">🇬🇧 Plymouth Univ.</button>
                    <button class="btn uni-btn btn-secondary" data-uni="UGC/VU" style="padding: 6px 16px; font-size: 0.82rem;">🎓 UGC / VU</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Products Grid -->
    <div class="grid" id="products-grid">
        <?php if (empty($products)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted); background: white; border-radius: var(--radius-md); border: 1px solid var(--border);">
                <h3 style="font-size: 1.4rem; color: var(--text-main); margin-bottom: 8px;">No listings available at the moment.</h3>
                <p>Be the first to add your product or Study Video Pack!</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $prod): ?>
                <?php
                $img_src = 'assets/uploads/' . ($prod['image_path'] ? $prod['image_path'] : 'placeholder.png');
                $has_video = !empty($prod['video_url']);
                ?>
                <div class="card product-card" 
                     data-id="<?php echo $prod['id']; ?>"
                     data-category="<?php echo $prod['category_id']; ?>"
                     data-category-name="<?php echo htmlspecialchars($prod['category_name']); ?>"
                     data-department="<?php echo htmlspecialchars($prod['department']); ?>"
                     data-university="<?php echo htmlspecialchars($prod['university']); ?>"
                     data-title="<?php echo htmlspecialchars($prod['title']); ?>"
                     data-desc="<?php echo htmlspecialchars($prod['description']); ?>">
                    
                    <div class="card-img-wrapper">
                        <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($prod['title']); ?>">
                        <span class="card-badge">
                            <?php echo $has_video ? '🎥 Study Video Pack' : htmlspecialchars($prod['category_name']); ?>
                        </span>
                    </div>
                    
                    <div class="card-body">
                        <!-- Department & University Badges -->
                        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 4px;">
                            <?php if ($prod['department'] !== 'General'): ?>
                                <span class="badge badge-dept-<?php echo strtolower($prod['department']); ?>">
                                    <?php echo $prod['department'] === 'Computing' ? '💻' : ($prod['department'] === 'Engineering' ? '⚙️' : '💼'); ?>
                                    <?php echo htmlspecialchars($prod['department']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($prod['university'] !== 'General'): ?>
                                <span class="badge badge-uni-<?php echo strtolower(str_replace('/', '', $prod['university'])); ?>">
                                    <?php echo $prod['university'] === 'Plymouth' ? '🇬🇧 Plymouth' : '🎓 UGC / VU'; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                            <h3 class="card-title"><?php echo htmlspecialchars($prod['title']); ?></h3>
                            <span class="card-price">LKR <?php echo number_format($prod['price'], 2); ?></span>
                        </div>
                        
                        <p class="card-desc"><?php echo htmlspecialchars($prod['description']); ?></p>
                        
                        <div class="card-footer">
                            <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
                                👤 Seller: <strong style="color: var(--text-main);"><?php echo htmlspecialchars($prod['seller_name']); ?></strong>
                            </span>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-secondary" style="padding: 8px 14px; font-size: 0.82rem;" 
                                        data-modal="details-modal"
                                        data-id="<?php echo $prod['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($prod['title']); ?>"
                                        data-description="<?php echo htmlspecialchars($prod['description']); ?>"
                                        data-price="<?php echo number_format($prod['price'], 2); ?>"
                                        data-category="<?php echo htmlspecialchars($prod['category_name']); ?>"
                                        data-department="<?php echo htmlspecialchars($prod['department']); ?>"
                                        data-university="<?php echo htmlspecialchars($prod['university']); ?>"
                                        data-video="<?php echo htmlspecialchars($prod['video_url'] ?? ''); ?>"
                                        data-image="<?php echo $img_src; ?>"
                                        data-seller="<?php echo htmlspecialchars($prod['seller_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($prod['seller_email']); ?>">
                                    Details
                                </button>
                                
                                <form action="cart.php" method="POST" class="add-to-cart-form">
                                    <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn btn-primary" style="padding: 8px 14px; font-size: 0.82rem;">
                                        🛒 Add
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Product Details Modal (Cinematic Window) -->
<div class="modal" id="details-modal">
    <div class="modal-content" style="max-width: 680px; padding: 0; overflow: hidden; border-radius: var(--radius-lg);">
        <div style="position: relative; height: 260px; background-color: #0f172a;">
            <img id="modal-image" src="" alt="" style="width: 100%; height: 100%; object-fit: cover;">
            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.85) 0%, transparent 60%);"></div>
            <button class="modal-close" style="position: absolute; top: 16px; right: 16px; z-index: 10;">&times;</button>
        </div>
        
        <div style="padding: 32px; display: flex; flex-direction: column; gap: 18px;">
            <div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                    <span id="modal-category" class="badge badge-approved" style="font-size: 0.75rem;">Category</span>
                    <span id="modal-dept-badge" class="badge badge-dept" style="display: none;">Department</span>
                    <span id="modal-uni-badge" class="badge badge-uni" style="display: none;">University</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                    <h2 id="modal-title" style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">Product Title</h2>
                    <span id="modal-price" style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark); font-family: 'Space Grotesk', sans-serif; white-space: nowrap;">LKR 0.00</span>
                </div>
            </div>
            
            <div>
                <h4 style="font-size: 0.85rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Resource Description</h4>
                <p id="modal-description" style="font-size: 0.95rem; color: var(--text-main); line-height: 1.6;"></p>
            </div>

            <!-- Video Preview Container (If video URL exists) -->
            <div id="modal-video-box" style="display: none; background: #0f172a; border-radius: var(--radius-sm); padding: 12px; border: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: #38bdf8;">🎥 STUDY VIDEO PACK PREVIEW</span>
                    <a id="modal-video-link" href="#" target="_blank" class="btn btn-video-play" style="padding: 4px 12px; font-size: 0.75rem;">▶️ Open Video Link</a>
                </div>
                <iframe id="modal-video-frame" src="" style="width: 100%; height: 220px; border: none; border-radius: 8px;" allowfullscreen></iframe>
            </div>

            <div style="background-color: var(--bg-main); border: 1px solid var(--border); padding: 16px 20px; border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-bottom: 2px;">STUDENT SELLER</span>
                    <strong id="modal-seller" style="font-size: 1rem; color: var(--text-main);">Username</strong>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-bottom: 2px;">CONTACT EMAIL</span>
                    <a id="modal-email" href="" style="color: var(--primary-dark); font-weight: 700; font-size: 0.9rem;">email@nsbm.ac.lk</a>
                </div>
            </div>
            
            <div style="display: flex; gap: 14px; margin-top: 6px;">
                <form id="modal-cart-form" action="cart.php" method="POST" style="flex: 1;">
                    <input type="hidden" name="product_id" id="modal-product-id" value="">
                    <input type="hidden" name="action" value="add">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem;">
                        🛒 Add to Shopping Cart
                    </button>
                </form>
                <button class="btn btn-secondary modal-close" style="padding: 14px 24px;">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Populate details modal dynamically
    const detailsButtons = document.querySelectorAll('button[data-modal="details-modal"]');
    
    detailsButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('modal-title').textContent = btn.dataset.title;
            document.getElementById('modal-description').textContent = btn.dataset.description;
            document.getElementById('modal-price').textContent = 'LKR ' + btn.dataset.price;
            document.getElementById('modal-category').textContent = btn.dataset.category;
            document.getElementById('modal-image').src = btn.dataset.image;
            document.getElementById('modal-seller').textContent = btn.dataset.seller;
            document.getElementById('modal-email').textContent = btn.dataset.email;
            document.getElementById('modal-email').href = 'mailto:' + btn.dataset.email;
            document.getElementById('modal-product-id').value = btn.dataset.id;

            // Department Badge
            const deptBadge = document.getElementById('modal-dept-badge');
            if (btn.dataset.department && btn.dataset.department !== 'General') {
                deptBadge.textContent = '🏢 ' + btn.dataset.department + ' Dept.';
                deptBadge.style.display = 'inline-flex';
            } else {
                deptBadge.style.display = 'none';
            }

            // University Badge
            const uniBadge = document.getElementById('modal-uni-badge');
            if (btn.dataset.university && btn.dataset.university !== 'General') {
                uniBadge.textContent = (btn.dataset.university === 'Plymouth' ? '🇬🇧 Plymouth Univ.' : '🎓 UGC / VU');
                uniBadge.style.display = 'inline-flex';
            } else {
                uniBadge.style.display = 'none';
            }

            // Video Preview Box
            const videoBox = document.getElementById('modal-video-box');
            const videoFrame = document.getElementById('modal-video-frame');
            const videoLink = document.getElementById('modal-video-link');
            if (btn.dataset.video && btn.dataset.video.trim() !== '') {
                videoBox.style.display = 'block';
                videoFrame.src = btn.dataset.video;
                videoLink.href = btn.dataset.video;
            } else {
                videoBox.style.display = 'none';
                videoFrame.src = '';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
