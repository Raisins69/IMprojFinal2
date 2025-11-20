<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/ProfanityFilter.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function handleResponse($success, $message = '', $data = [], $isAjax = true) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    } else {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $success ? 'success' : 'error';
        
        $redirect = $data['redirect'] ?? BASE_URL . '/cart/cart.php';
        header('Location: ' . $redirect);
        exit();
    }
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    handleResponse(false, 'Please login to add items to cart', 
        ['redirect' => BASE_URL . '/login.php'], 
        $isAjax
    );
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    handleResponse(false, 'Invalid product', [], $isAjax);
}

$product_id = intval($_GET['id']);
$customer_id = $_SESSION['user_id'];
$quantity = isset($_GET['quantity']) ? max(1, intval($_GET['quantity'])) : 1;

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        throw new Exception('Product not found or out of stock');
    }
    
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE product_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $product_id, $customer_id);
    $stmt->execute();
    $cart_item = $stmt->get_result()->fetch_assoc();
    
    $new_quantity = $cart_item ? ($cart_item['quantity'] + $quantity) : $quantity;
    
    if ($new_quantity > $product['stock']) {
        throw new Exception('Not enough stock available. Only ' . $product['stock'] . ' items left in stock.');
    }
    
    if ($cart_item) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $customer_id, $product_id, $quantity);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update cart. Please try again.');
    }
    
    $conn->commit();
    
    $responseData = [
        'message' => 'Product added to cart successfully!',
        'cartCount' => $new_quantity
    ];
    
    if (isset($_GET['return_url'])) {
        $return_url = urldecode($_GET['return_url']);
        if (strpos($return_url, BASE_URL) === 0 || strpos($return_url, '/') === 0) {
            $responseData['redirect'] = $return_url;
        }
    }
    
    handleResponse(true, $responseData['message'], $responseData, $isAjax);
    
} catch (Exception $e) {
    $conn->rollback();
    handleResponse(false, $e->getMessage(), [], $isAjax);
}
?>
