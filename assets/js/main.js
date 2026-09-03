// NSBM Marketplace Static JS Controller for Vercel Hosting

document.addEventListener('DOMContentLoaded', () => {
    updateCartBadge();

    const productsGrid = document.getElementById('products-grid');
    if (!productsGrid) return;

    const products = getProducts().filter(p => p.status === 'approved');

    let activeCategory = 'all';
    let activeCategoryName = 'all';
    let activeDept = 'all';
    let activeUni = 'all';
    let searchQuery = '';

    const renderProducts = () => {
        productsGrid.innerHTML = '';

        const filtered = products.filter(p => {
            const title = (p.title || '').toLowerCase();
            const desc = (p.description || '').toLowerCase();
            const matchesSearch = title.includes(searchQuery) || desc.includes(searchQuery);
            const matchesCategory = activeCategory === 'all' || p.category_id === activeCategory;
            const matchesDept = activeDept === 'all' || p.department === activeDept;
            const matchesUni = activeUni === 'all' || p.university === activeUni;
            return matchesSearch && matchesCategory && matchesDept && matchesUni;
        });

        if (filtered.length === 0) {
            productsGrid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted); background: white; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <h3 style="font-size: 1.4rem; color: var(--text-main); margin-bottom: 8px;">No listings available matching filters.</h3>
                    <p>Try resetting search or selecting another category.</p>
                </div>
            `;
            return;
        }

        filtered.forEach(p => {
            const hasVideo = !empty(p.video_url);
            const card = document.createElement('div');
            card.className = 'card product-card';
            card.style.animation = 'fadeIn 0.35s ease forwards';
            
            card.innerHTML = `
                <div class="card-img-wrapper">
                    <img src="${p.image_path || 'assets/uploads/placeholder.png'}" alt="${escapeHtml(p.title)}">
                    <span class="card-badge">
                        ${hasVideo ? '🎥 Study Video Pack' : escapeHtml(p.category_name)}
                    </span>
                </div>
                
                <div class="card-body">
                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 4px;">
                        ${p.department && p.department !== 'General' ? `<span class="badge badge-dept-${p.department.toLowerCase()}">${p.department === 'Computing' ? '💻' : p.department === 'Engineering' ? '⚙️' : '💼'} ${escapeHtml(p.department)}</span>` : ''}
                        ${p.university && p.university !== 'General' ? `<span class="badge badge-uni-${p.university.toLowerCase().replace('/', '')}">${p.university === 'Plymouth' ? '🇬🇧 Plymouth' : '🎓 UGC / VU'}</span>` : ''}
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                        <h3 class="card-title">${escapeHtml(p.title)}</h3>
                        <span class="card-price">LKR ${numberFormat(p.price)}</span>
                    </div>
                    
                    <p class="card-desc">${escapeHtml(p.description)}</p>
                    
                    <div class="card-footer">
                        <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
                            👤 Seller: <strong style="color: var(--text-main);">${escapeHtml(p.seller_name)}</strong>
                        </span>
                        
                        <div style="display: flex; gap: 8px;">
                            <button class="btn btn-secondary details-btn" style="padding: 8px 14px; font-size: 0.82rem;" data-id="${p.id}">Details</button>
                            <button class="btn btn-primary add-cart-btn" style="padding: 8px 14px; font-size: 0.82rem;" data-id="${p.id}">🛒 Add</button>
                        </div>
                    </div>
                </div>
            `;

            productsGrid.appendChild(card);
        });

        // Attach Details modal listeners
        document.querySelectorAll('.details-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const item = products.find(p => p.id == id);
                if (!item) return;

                document.getElementById('modal-title').textContent = item.title;
                document.getElementById('modal-description').textContent = item.description;
                document.getElementById('modal-price').textContent = 'LKR ' + numberFormat(item.price);
                document.getElementById('modal-category').textContent = item.category_name;
                document.getElementById('modal-image').src = item.image_path || 'assets/uploads/placeholder.png';
                document.getElementById('modal-seller').textContent = item.seller_name;
                document.getElementById('modal-email').textContent = item.seller_email;
                document.getElementById('modal-email').href = 'mailto:' + item.seller_email;

                const deptBadge = document.getElementById('modal-dept-badge');
                if (item.department && item.department !== 'General') {
                    deptBadge.textContent = '🏢 ' + item.department + ' Dept.';
                    deptBadge.style.display = 'inline-flex';
                } else { deptBadge.style.display = 'none'; }

                const uniBadge = document.getElementById('modal-uni-badge');
                if (item.university && item.university !== 'General') {
                    uniBadge.textContent = (item.university === 'Plymouth' ? '🇬🇧 Plymouth Univ.' : '🎓 UGC / VU');
                    uniBadge.style.display = 'inline-flex';
                } else { uniBadge.style.display = 'none'; }

                const videoBox = document.getElementById('modal-video-box');
                const videoFrame = document.getElementById('modal-video-frame');
                const videoLink = document.getElementById('modal-video-link');
                if (item.video_url && item.video_url.trim() !== '') {
                    videoBox.style.display = 'block';
                    videoFrame.src = item.video_url;
                    videoLink.href = item.video_url;
                } else {
                    videoBox.style.display = 'none';
                    videoFrame.src = '';
                }

                const addModalBtn = document.getElementById('modal-add-cart-btn');
                addModalBtn.onclick = () => { addToCart(item.id); };

                document.getElementById('details-modal').classList.add('active');
            });
        });

        // Attach Add to Cart listeners
        document.querySelectorAll('.add-cart-btn').forEach(btn => {
            btn.addEventListener('click', () => { addToCart(btn.dataset.id); });
        });
    };

    renderProducts();

    // Filters logic
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase();
            renderProducts();
        });
    }

    const categoryButtons = document.querySelectorAll('.category-btn');
    const subFilterContainer = document.getElementById('sub-filter-container');
    const deptButtons = document.querySelectorAll('.dept-btn');
    const uniButtons = document.querySelectorAll('.uni-btn');

    categoryButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            categoryButtons.forEach(b => b.classList.remove('active', 'btn-primary'));
            categoryButtons.forEach(b => b.classList.add('btn-secondary'));

            btn.classList.remove('btn-secondary');
            btn.classList.add('active', 'btn-primary');

            activeCategory = btn.dataset.category;
            activeCategoryName = btn.dataset.categoryName || '';

            if (subFilterContainer) {
                if (activeCategoryName === 'Student Services' || activeCategory === '5') {
                    subFilterContainer.style.display = 'block';
                } else {
                    subFilterContainer.style.display = 'none';
                    activeDept = 'all';
                    activeUni = 'all';
                }
            }

            renderProducts();
        });
    });

    deptButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            deptButtons.forEach(b => b.classList.remove('active', 'btn-primary'));
            deptButtons.forEach(b => b.classList.add('btn-secondary'));
            btn.classList.remove('btn-secondary');
            btn.classList.add('active', 'btn-primary');
            activeDept = btn.dataset.dept;
            renderProducts();
        });
    });

    uniButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            uniButtons.forEach(b => b.classList.remove('active', 'btn-primary'));
            uniButtons.forEach(b => b.classList.add('btn-secondary'));
            btn.classList.remove('btn-secondary');
            btn.classList.add('active', 'btn-primary');
            activeUni = btn.dataset.uni;
            renderProducts();
        });
    });

    // Close Modals
    document.querySelectorAll('.modal-close').forEach(close => {
        close.addEventListener('click', () => {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
        });
    });
});

function empty(val) { return !val || val === '' || val === null; }
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function numberFormat(num) { return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'); }
