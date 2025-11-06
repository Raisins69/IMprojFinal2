<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$msg = "";
$customers = $conn->query("SELECT * FROM customers ORDER BY name");
$products = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY name");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = intval($_POST['customer_id']);
    $payment_method = trim($_POST['payment_method']);
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    
    if (empty($customer_id) || empty($product_ids)) {
        $msg = "❌ Please select customer and at least one product.";
    } else {
        $conn->begin_transaction();
        
        try {
            $total = 0;
            $order_items = [];
            
            // Validate products and calculate total
            foreach ($product_ids as $index => $product_id) {
                $qty = intval($quantities[$index]);
                if ($qty <= 0) continue;
                
                $stmt = $conn->prepare("SELECT price, stock FROM products WHERE id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                if (!$product || $product['stock'] < $qty) {
                    throw new Exception("Insufficient stock for product ID: $product_id");
                }
                
                $order_items[] = [
                    'product_id' => $product_id,
                    'quantity' => $qty,
                    'price' => $product['price']
                ];
                
                $total += $product['price'] * $qty;
            }
            
            if (empty($order_items)) {
                throw new Exception("No valid products selected");
            }
            
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (customer_id, order_date, payment_method, total_amount, status) 
                                   VALUES (?, NOW(), ?, ?, 'Completed')");
            $stmt->bind_param("isd", $customer_id, $payment_method, $total);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Insert order items and update stock
            foreach ($order_items as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt->execute();
                
                // Update stock
                $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt->execute();
            }
            
            $conn->commit();
            $msg = "✅ Transaction created successfully! Order ID: $order_id";
            
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "❌ Failed: " . $e->getMessage();
        }
    }
}

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Create Manual Transaction</h2>

        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST">
            <label>Customer *</label>
            <select name="customer_id" required>
                <option value="">Select Customer</option>
                <?php while($customer = $customers->fetch_assoc()): ?>
                    <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?> - <?= htmlspecialchars($customer['email']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Payment Method</label>
            <select name="payment_method">
                <option value="Cash">Cash</option>
                <option value="GCash">GCash</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Debit Card">Debit Card</option>
                <option value="Bank Transfer">Bank Transfer</option>
            </select>

            <h3>Products</h3>
            <div id="products-container">
                <div class="product-row" style="display: grid; grid-template-columns: 2fr 1fr 50px; gap: 10px; margin-bottom: 10px;">
                    <select name="product_id[]" required>
                        <option value="">Select Product</option>
                        <?php 
                        $products_copy = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY name");
                        while($product = $products_copy->fetch_assoc()): 
                        ?>
                            <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?> - ₱<?= number_format($product['price'], 2) ?> (Stock: <?= $product['stock'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                    <input type="number" name="quantity[]" placeholder="Qty" min="1" value="1" required>
                    <button type="button" onclick="removeProduct(this)" class="btn-delete">✖</button>
                </div>
            </div>

            <button type="button" onclick="addProduct()" class="btn-secondary">➕ Add Product</button>
            <br><br>

            <button type="submit" class="btn-primary">Create Transaction</button>
            <a href="read.php" class="btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<script>
function addProduct() {
    const container = document.getElementById('products-container');
    const firstRow = container.querySelector('.product-row');
    const newRow = firstRow.cloneNode(true);
    container.appendChild(newRow);
}

function removeProduct(btn) {
    const container = document.getElementById('products-container');
    if (container.querySelectorAll('.product-row').length > 1) {
        btn.closest('.product-row').remove();
    } else {
        alert('At least one product is required');
    }
}
</script>

<?php include '../../../includes/footer.php'; ?>
