<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['supplier_id'])) {
    header("Location: read.php");
    exit();
}

$delivery_id = intval($_GET['id']);
$supplier_id = intval($_GET['supplier_id']);

$stmt = $conn->prepare("DELETE FROM supplier_deliveries WHERE id = ?");
$stmt->bind_param("i", $delivery_id);

if ($stmt->execute()) {
    header("Location: deliveries.php?supplier_id=$supplier_id&msg=deleted");
} else {
    header("Location: deliveries.php?supplier_id=$supplier_id&msg=error");
}
exit();
?>
