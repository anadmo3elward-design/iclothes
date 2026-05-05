<?php
require_once 'config.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM `copouns` LIKE 'StartDate'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            ALTER TABLE `copouns`
            ADD COLUMN `StartDate` DATE DEFAULT NULL AFTER `DiscountAmount`,
            ADD COLUMN `EndDate` DATE DEFAULT NULL AFTER `StartDate`;
        ");
        echo "Added StartDate and EndDate to copouns table.\n";
    } else {
        echo "StartDate and EndDate already exist in copouns table.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
