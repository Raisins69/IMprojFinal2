<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['supplier_id']) || !is_numeric($_GET['supplier_id'])) {
    header("Location: read.php");
    exit();
}

$supplier_id = intval($_GET['supplier_id']);

// Get supplier info
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();

if (!$supplier) {
    header("Location: read.php");
    exit();
}

// Get deliveries for this supplier (FR3.2)
$stmt = $conn->prepare("
    SELECT sd.*, p.name as product_name
    FROM supplier_deliveries sd
    JOIN products p ON sd.product_id = p.id
    WHERE sd.supplier_id = ?
    ORDER BY sd.delivery_date DESC
");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$deliveries = $stmt->get_result();

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Deliveries from: <?= htmlspecialchars($supplier['name']) ?></h2>
        <p><strong>Contact:</strong> <?= htmlspecialchars($supplier['contact_person']) ?> - <?= htmlspecialchars($supplier['contact_number']) ?></p>

        <a href="add_delivery.php?supplier_id=<?= $supplier_id ?>" class="btn-primary">➕ Add Delivery</a>

        <?php if ($deliveries->num_rows > 0): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Cost</th>
                        <th>Delivery Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_cost = 0;
                    $total_quantity = 0;
                    while($delivery = $deliveries->fetch_assoc()): 
                        $total_cost += $delivery['cost'];
                        $total_quantity += $delivery['quantity'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($delivery['id']) ?></td>
                            <td><?= htmlspecialchars($delivery['product_name']) ?></td>
                            <td><?= htmlspecialchars($delivery['quantity']) ?></td>
                            <td>₱<?= number_format($delivery['cost'], 2) ?></td>
                            <td><?= htmlspecialchars($delivery['delivery_date']) ?></td>
                            <td>
                                <a class="btn-edit" href="edit_delivery.php?id=<?= intval($delivery['id']) ?>">✏ Edit</a>
                                <a class="btn-delete" href="delete_delivery.php?id=<?= intval($delivery['id']) ?>&supplier_id=<?= $supplier_id ?>" 
                                   onclick="return confirm('Delete this delivery record?');">🗑 Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div style="margin-top: 20px; padding: 15px; background: #12121A; border-radius: 8px;">
                <h3>Summary</h3>
                <p><strong>Total Deliveries:</strong> <?= $deliveries->num_rows ?></p>
                <p><strong>Total Quantity Supplied:</strong> <?= $total_quantity ?></p>
                <p><strong>Total Cost:</strong> ₱<?= number_format($total_cost, 2) ?></p>
            </div>
        <?php else: ?>
            <p style="text-align: center; padding: 20px; color: #888;">No deliveries recorded yet.</p>
        <?php endif; ?>

        <br>
        <a href="read.php" class="btn-secondary">⬅ Back to Suppliers</a>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
