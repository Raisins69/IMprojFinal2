<?php
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$token = $_GET['token'] ?? '';

if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid or missing CSRF token';
    header('Location: read.php');
    exit();
}

if (!$id) {
    $_SESSION['error'] = 'Invalid supplier ID';
    header('Location: read.php');
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ?");
    if ($stmt === false) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Supplier not found';
        header('Location: read.php');
        exit();
    }
    
    $checkStmt = $conn->prepare("
        (SELECT 'products' AS table_name, COUNT(*) AS count FROM products WHERE supplier_id = ?)
        UNION ALL
        (SELECT 'deliveries' AS table_name, COUNT(*) AS count FROM supplier_deliveries WHERE supplier_id = ?)
    ");
    
    if ($checkStmt === false) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $checkStmt->bind_param("ii", $id, $id);
    if (!$checkStmt->execute()) {
        throw new Exception('Query execution failed: ' . $checkStmt->error);
    }
    
    $relatedRecords = $checkStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $hasRelatedRecords = false;
    $relatedCounts = [];
    
    foreach ($relatedRecords as $record) {
        if ($record['count'] > 0) {
            $hasRelatedRecords = true;
            $relatedCounts[$record['table_name']] = $record['count'];
        }
    }
    
    $conn->begin_transaction();
    
    try {
        if ($hasRelatedRecords) {
            if (isset($relatedCounts['products'])) {
                $updateProducts = $conn->prepare("UPDATE products SET supplier_id = NULL WHERE supplier_id = ?");
                if ($updateProducts === false) {
                    throw new Exception('Failed to prepare products update: ' . $conn->error);
                }
                $updateProducts->bind_param("i", $id);
                if (!$updateProducts->execute()) {
                    throw new Exception('Failed to update products: ' . $updateProducts->error);
                }
            }
            
            if (isset($relatedCounts['deliveries'])) {
                $deleteDeliveries = $conn->prepare("DELETE FROM supplier_deliveries WHERE supplier_id = ?");
                if ($deleteDeliveries === false) {
                    throw new Exception('Failed to prepare deliveries delete: ' . $conn->error);
                }
                $deleteDeliveries->bind_param("i", $id);
                if (!$deleteDeliveries->execute()) {
                    throw new Exception('Failed to delete deliveries: ' . $deleteDeliveries->error);
                }
            }
        }

        $deleteStmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
        if ($deleteStmt === false) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        
        $deleteStmt->bind_param("i", $id);
        if (!$deleteStmt->execute()) {
            throw new Exception('Delete failed: ' . $deleteStmt->error);
        }
        
        $conn->commit();
        
        if ($hasRelatedRecords) {
            $relatedMessages = [];
            if (isset($relatedCounts['products'])) {
                $relatedMessages[] = $relatedCounts['products'] . ' product(s) were unassigned';
            }
            if (isset($relatedCounts['deliveries'])) {
                $relatedMessages[] = $relatedCounts['deliveries'] . ' delivery record(s) were removed';
            }
            $_SESSION['success'] = 'Supplier deleted successfully. ' . implode(', ', $relatedMessages) . '.';
        } else {
            $_SESSION['success'] = 'Supplier deleted successfully';
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Delete supplier error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while deleting the supplier. Please try again.';
}

header('Location: read.php');
exit();
