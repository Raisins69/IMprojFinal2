<?php
include __DIR__ . '/../../includes/config.php';

// ✅ Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>

<?php include '../../includes/header.php'; ?>

<div class="admin-container">
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Dashboard Content -->
    <main class="admin-content">
        <h2>Welcome Admin 👑</h2>
        <p>Manage UrbanThrift system here.</p>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Products</h3>
                <p>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as total FROM products");
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                    ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Total Customers</h3>
                <p>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='customer'");
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                    ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Total Sales</h3>
                <p>
                    ₱
                    <?php
                    // ✅ Fixed: Query orders table instead of non-existent sales table
                    $result = $conn->query("SELECT SUM(total_amount) as income FROM orders WHERE status = 'Completed'");
                    $row = $result->fetch_assoc();
                    echo number_format($row['income'] ?? 0, 2);
                    ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Total Orders</h3>
                <p>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as total FROM orders");
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                    ?>
                </p>
            </div>
        </div>

        <!-- Recent Orders -->
        <div style="margin-top: 3rem;">
            <h3 style="color: var(--primary-light); margin-bottom: 1.5rem;">📦 Recent Orders</h3>
            <?php
            $recent_orders = $conn->query("SELECT o.id, o.order_date, o.total_amount, o.status, c.name as customer_name 
                                          FROM orders o 
                                          JOIN customers c ON o.customer_id = c.id 
                                          ORDER BY o.order_date DESC 
                                          LIMIT 5");
            
            if ($recent_orders && $recent_orders->num_rows > 0):
            ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $recent_orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <span style="padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600;
                                        background: <?= $order['status'] == 'Completed' ? 'rgba(0, 217, 165, 0.2)' : 'rgba(255, 176, 32, 0.2)' ?>;
                                        color: <?= $order['status'] == 'Completed' ? '#00D9A5' : '#FFB020' ?>;">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="transactions/view.php?id=<?= $order['id'] ?>" class="btn-view">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; padding: 2rem; color: var(--text-secondary);">No orders yet.</p>
            <?php endif; ?>
        </div>

        <!-- Low Stock Alert -->
        <div style="margin-top: 3rem;">
            <h3 style="color: var(--warning); margin-bottom: 1.5rem;">⚠️ Low Stock Alert</h3>
            <?php
            $low_stock = $conn->query("SELECT * FROM products WHERE stock <= 5 AND stock > 0 ORDER BY stock ASC LIMIT 5");
            
            if ($low_stock && $low_stock->num_rows > 0):
            ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Brand</th>
                        <th>Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = $low_stock->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= htmlspecialchars($product['brand']) ?></td>
                        <td>
                            <span style="padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600;
                                        background: rgba(255, 71, 87, 0.2); color: #FF4757;">
                                <?= $product['stock'] ?> left
                            </span>
                        </td>
                        <td>
                            <a href="products/update.php?id=<?= $product['id'] ?>" class="btn-edit">Restock</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; padding: 2rem; color: var(--success);">✅ All products have sufficient stock.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>