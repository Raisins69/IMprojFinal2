<?php
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

$error = '';
$delivery_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$supplier_id = filter_input(INPUT_GET, 'supplier_id', FILTER_VALIDATE_INT);
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid or missing CSRF token';
    header('Location: deliveries.php' . ($supplier_id ? '?supplier_id=' . $supplier_id : ''));
    exit();
}

if (!$delivery_id || !$supplier_id) {
    $_SESSION['error'] = 'Invalid delivery or supplier ID';
    header('Location: deliveries.php' . ($supplier_id ? '?supplier_id=' . $supplier_id : ''));
    exit();
}

try {
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("
            SELECT id, reference_number, delivery_date, status 
            FROM supplier_deliveries 
            WHERE id = ? AND supplier_id = ?
        ");
        
        if ($stmt === false) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $delivery_id, $supplier_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Query execution failed: ' . $stmt->error);
        }
        
        $delivery = $stmt->get_result()->fetch_assoc();
        
        if (!$delivery) {
            throw new Exception('Delivery not found or does not belong to the specified supplier');
        }
        
        if (in_array($delivery['status'], ['Received', 'In Transit'])) {
            throw new Exception('Cannot delete a delivery that is already ' . $delivery['status']);
        }
        
        $deleteItemsStmt = $conn->prepare("DELETE FROM delivery_items WHERE delivery_id = ?");
        if ($deleteItemsStmt === false) {
            throw new Exception('Failed to prepare delivery items deletion: ' . $conn->error);
        }
        
        $deleteItemsStmt->bind_param("i", $delivery_id);
        if (!$deleteItemsStmt->execute()) {
            throw new Exception('Failed to delete delivery items: ' . $deleteItemsStmt->error);
        }
        
        $deleteStmt = $conn->prepare("DELETE FROM supplier_deliveries WHERE id = ? AND supplier_id = ?");
        if ($deleteStmt === false) {
            throw new Exception('Failed to prepare delivery deletion: ' . $conn->error);
        }
        
        $deleteStmt->bind_param("ii", $delivery_id, $supplier_id);
        
        if (!$deleteStmt->execute()) {
            throw new Exception('Failed to delete delivery: ' . $deleteStmt->error);
        }
        
        if ($deleteStmt->affected_rows === 0) {
            throw new Exception('No delivery was deleted. It may have already been deleted.');
        }
        
        $logMessage = sprintf(
            "Delivery #%s (Ref: %s) deleted by user ID %s",
            $delivery_id,
            $delivery['reference_number'] ?? 'N/A',
            $_SESSION['user_id'] ?? 'unknown'
        );
        
        error_log($logMessage);
        
        $conn->commit();
        
        $_SESSION['success'] = 'Delivery deleted successfully';
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Delete delivery error: ' . $e->getMessage());
    $_SESSION['error'] = 'Failed to delete delivery: ' . $e->getMessage();
}

// Redirect back to deliveries page
header('Location: deliveries.php?supplier_id=' . $supplier_id);
exit();
