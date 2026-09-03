    </main>
    <footer>
        <div class="container footer-content">
            <div>
                <h3 style="color: white; margin-bottom: 10px;">EcoMart</h3>
                <p style="font-size: 0.9rem;">Empowering student entrepreneurs and service providers at NSBM Green University.</p>
            </div>
            <div style="display: flex; gap: 30px;">
                <div>
                    <h4 style="color: white; margin-bottom: 10px; font-size: 0.95rem;">Quick Links</h4>
                    <ul style="list-style: none; font-size: 0.85rem; display: flex; flex-direction: column; gap: 6px;">
                        <li><a href="/index.php">Marketplace</a></li>
                        <li><a href="/login.php">Login / Register</a></li>
                        <li><a href="/cart.php">Shopping Cart</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-copy">
            <p>&copy; <?php echo date('Y'); ?> EcoMart Marketplace. Built with Passion.</p>
        </div>
    </footer>
    <script src="/assets/js/main.js"></script>
    <?php if (basename($_SERVER['PHP_SELF']) === 'login.php'): ?>
        <script src="/assets/js/auth.js"></script>
    <?php endif; ?>
</body>
</html>
