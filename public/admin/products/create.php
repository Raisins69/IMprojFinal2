<?php
require_once __DIR__ . '/../../../includes/config.php';

// Check admin access
checkAdmin();

$msg = "";

// Fetch all active suppliers
$suppliers = [];
try {
    $supplierQuery = "SELECT id, name FROM suppliers ORDER BY name";
    $supplierResult = $conn->query($supplierQuery);
    if ($supplierResult) {
        $suppliers = $supplierResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching suppliers: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $category = trim($_POST['category']);
    $size = trim($_POST['size']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $condition_type = trim($_POST['condition_type']);
    $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;

    // Validate inputs
    if (empty($name) || empty($brand) || empty($category) || $price <= 0 || $stock < 0) {
        $msg = "❌ Please fill all required fields with valid data.";
    } elseif (!isset($_FILES["image"]) || $_FILES["image"]["error"] != 0) {
        $msg = "❌ Please upload a valid image.";
    } else {
        $image = $_FILES["image"]["name"];
        $target = "../../uploads/" . basename($image);
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES["image"]["type"], $allowed_types)) {
            $msg = "❌ Only JPG, PNG, GIF, and WEBP images are allowed.";
        } else {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert product
                    $stmt = $conn->prepare("INSERT INTO products (name, brand, category, size, price, stock, condition_type, image)
                                        VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->bind_param("ssssdiis", $name, $brand, $category, $size, $price, $stock, $condition_type, $image);
                    
                    if ($stmt->execute()) {
                        $product_id = $conn->insert_id;
                        
                        // If supplier is selected, add to supplier_products
                        if ($supplier_id > 0) {
                            $supplierStmt = $conn->prepare("INSERT INTO supplier_products (supplier_id, product_id, is_primary) VALUES (?, ?, 1)");
                            $supplierStmt->bind_param("ii", $supplier_id, $product_id);
                            $supplierStmt->execute();
                            $supplierStmt->close();
                        }
                        
                        $conn->commit();
                        $msg = "✅ Product Added Successfully!";
                        // Clear the form
                        $name = $brand = $category = $size = '';
                        $price = $stock = 0;
                } else {
                    throw new Exception("Failed to add product");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "❌ Failed to add product: " . $e->getMessage();
                // Delete the uploaded image if database insert failed
                if (file_exists($target)) {
                    unlink($target);
                }
            }
            } else {
                $msg = "❌ Error uploading image. Please try again.";
            }
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Add Product</h2>

        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST" enctype="multipart/form-data" id="productForm" novalidate>
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       class="form-input"
                       data-required="true"
                       data-min-length="3"
                       data-max-length="100"
                       data-pattern-message="Product name must be between 3-100 characters">
            </div>

            <div class="form-group">
                <label for="brand">Brand</label>
                <input type="text" 
                       id="brand" 
                       name="brand" 
                       class="form-input"
                       data-required="true"
                       data-min-length="2"
                       data-max-length="50"
                       data-pattern-message="Brand must be between 2-50 characters">
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" 
                       id="category" 
                       name="category" 
                       class="form-input"
                       data-required="true"
                       data-pattern="^[a-zA-Z\s&]+"
                       data-pattern-message="Please enter a valid category name">
            </div>

            <div class="form-group">
                <label for="size">Size</label>
                <input type="text" 
                       id="size" 
                       name="size" 
                       class="form-input"
                       data-required="true"
                       data-pattern-message="Please specify the product size">
            </div>

            <div class="form-group">
                <label for="price">Price (₱)</label>
                <input type="number" 
                       id="price" 
                       name="price" 
                       class="form-input"
                       step="0.01" 
                       min="0.01"
                       data-required="true"
                       data-pattern="^\d+(\.\d{1,2})?$"
                       data-pattern-message="Please enter a valid price">
            </div>

            <div class="form-group">
                <label for="stock">Stock Quantity</label>
                <input type="number" 
                       id="stock" 
                       name="stock" 
                       class="form-input"
                       min="0"
                       data-required="true"
                       data-pattern="^\d+$"
                       data-pattern-message="Please enter a valid stock quantity">
            </div>

            <div class="form-group">
                <label for="condition_type">Condition Type</label>
                <select name="condition_type" 
                        id="condition_type" 
                        class="form-input"
                        data-required="true">
                    <option value="">-- Select Condition --</option>
                    <option value="Like New">Like New</option>
                    <option value="Good">Good</option>
                    <option value="Slightly Used">Slightly Used</option>
                </select>
            </div>

            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select name="supplier_id" 
                        id="supplier_id" 
                        class="form-input"
                        data-required="true"
                        data-pattern-message="Please select a supplier">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= htmlspecialchars($supplier['id']) ?>">
                            <?= htmlspecialchars($supplier['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($suppliers)): ?>
                    <p class="text-warning">No suppliers available. <a href="../suppliers/create.php">Add a supplier first</a>.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" 
                       id="image" 
                       name="image" 
                       class="form-input"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       data-required="true"
                       data-file-type="image/jpeg,image/png,image/gif,image/webp"
                       data-max-size="2MB"
                       data-pattern-message="Please upload a valid image (JPG, PNG, GIF, or WebP, max 2MB)">
                <small class="text-muted">Allowed formats: JPG, PNG, GIF, WebP. Max size: 2MB</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    💾 Save Product
                </button>
                <a href="index.php" class="btn-secondary">
                    ❌ Cancel
                </a>
            </div>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    const form = document.getElementById('productForm');
    
    // Add event listener for form submission
    form.addEventListener('submit', function(e) {
        // The form validation will be handled by form-validation.js
        // This just ensures the form is properly initialized
    });
    
    // Add image preview functionality
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }
                
                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Please upload a valid image file (JPG, PNG, GIF, or WebP)');
                    this.value = '';
                    return;
                }
                
                // Show image preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    // Remove existing preview if any
                    const existingPreview = document.getElementById('imagePreview');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    // Create preview image
                    const preview = document.createElement('img');
                    preview.id = 'imagePreview';
                    preview.src = event.target.result;
                    preview.style.maxWidth = '200px';
                    preview.style.marginTop = '10px';
                    preview.style.borderRadius = '4px';
                    preview.style.border = '1px solid #ddd';
                    
                    // Insert after the file input
                    imageInput.parentNode.insertBefore(preview, imageInput.nextSibling);
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>