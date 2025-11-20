<?php
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

header("Location: /projectIManagement/public/admin/expenses/read.php");
exit();
?>
