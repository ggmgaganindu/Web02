<?php
require_once '../config/db.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

try {
    // 1. Stats counters
    $user_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $prod_total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $prod_pending = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'pending'")->fetchColumn();
    $sales_volume = $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn() ?: 0.00;
    
    // 2. Recent sales reports
    $orders_stmt = $pdo->query("
        SELECT o.id as order_id, o.total_amount, o.created_at, u.username as buyer_name
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $recent_orders = $orders_stmt->fetchAll();

    // 3. Sales by category for Chart.js
    $chart_stmt = $pdo->query("
        SELECT c.name as category_name, SUM(oi.price * oi.quantity) as sales_sum
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        GROUP BY c.id
    ");
    $chart_data = $chart_stmt->fetchAll();
    
    $labels = [];
    $data = [];
    foreach ($chart_data as $row) {
        $labels[] = $row['category_name'];
        $data[] = $row['sales_sum'];
    }

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
            <li><a href="index.php" class="sidebar-link active">📊 Dashboard Home</a></li>
            <li><a href="users.php" class="sidebar-link">👥 Manage Users</a></li>
            <li><a href="products.php" class="sidebar-link"> Moderation Queue (<?php echo $prod_pending; ?>)</a></li>
            <li><a href="categories.php" class="sidebar-link">📁 Categories CRUD</a></li>
        </ul>
    </aside>

    <!-- Main Content Panel -->
    <div class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 style="font-weight: 800; font-size: 1.8rem; color: #0f172a;">Admin Analytics</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">System overview, statistics, and sales performance charts.</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card" style="border-left: 4px solid var(--primary);">
                <span class="stat-label">Total Student Users</span>
                <span class="stat-value"><?php echo $user_count; ?></span>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--warning);">
                <span class="stat-label">Pending Approval</span>
                <span class="stat-value"><?php echo $prod_pending; ?></span>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--success);">
                <span class="stat-label">Mock Sales Volume</span>
                <span class="stat-value" style="font-size: 1.6rem; margin-top: 13px;">LKR <?php echo number_format($sales_volume, 2); ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Total Submissions</span>
                <span class="stat-value"><?php echo $prod_total; ?></span>
            </div>
        </div>

        <!-- Chart Section -->
        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-sm); margin-bottom: 30px;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin-bottom: 20px;">Sales Distribution by Category</h3>
            <div style="height: 300px; position: relative; width: 100%;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Recent Simulated Orders -->
        <h2 style="font-weight: 800; font-size: 1.4rem; color: #0f172a; margin-bottom: 15px;">Recent Simulated Orders</h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order Reference</th>
                        <th>Student Buyer</th>
                        <th>Total Amount</th>
                        <th>Order Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_orders)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No simulated checkout transactions recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $ord): ?>
                            <tr>
                                <td>#NSBM-<?php echo str_pad($ord['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><strong><?php echo htmlspecialchars($ord['buyer_name']); ?></strong></td>
                                <td><strong>LKR <?php echo number_format($ord['total_amount'], 2); ?></strong></td>
                                <td><?php echo date('M d, Y H:i', strtotime($ord['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const labels = <?php echo json_encode($labels); ?>;
    const data = <?php echo json_encode($data); ?>;
    
    if (labels.length === 0) {
        // Fallback display if no sales
        ctx.font = '16px Plus Jakarta Sans';
        ctx.fillStyle = '#64748b';
        ctx.fillText('No Sales Data to display yet', 10, 50);
        return;
    }

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales (LKR)',
                data: data,
                backgroundColor: 'rgba(0, 156, 185, 0.65)',
                borderColor: 'rgba(0, 156, 185, 1)',
                borderWidth: 1.5,
                borderRadius: 8,
                barPercentage: 0.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    },
                    ticks: {
                        font: {
                            family: 'Plus Jakarta Sans'
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Plus Jakarta Sans'
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
