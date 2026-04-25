<?php
require_once 'config.php';

try {
    // 1. Create sub_category table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sub_category` (
          `SubCategoryID` INT AUTO_INCREMENT PRIMARY KEY,
          `CategoryID` INT NOT NULL,
          `Name` VARCHAR(150) NOT NULL,
          `CreatedBy` INT,
          FOREIGN KEY (`CategoryID`) REFERENCES `category`(`CategoryID`) ON DELETE CASCADE,
          FOREIGN KEY (`CreatedBy`) REFERENCES `admins`(`AdminID`) ON DELETE SET NULL
        ) ENGINE=InnoDB;
    ");
    echo "Created sub_category table.\\n";

    // 2. Add SubCategoryID to item table if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM `item` LIKE 'SubCategoryID'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            ALTER TABLE `item`
            ADD COLUMN `SubCategoryID` INT DEFAULT NULL AFTER `CategoryID`,
            ADD FOREIGN KEY (`SubCategoryID`) REFERENCES `sub_category`(`SubCategoryID`) ON DELETE SET NULL;
        ");
        echo "Added SubCategoryID to item table.\\n";
    } else {
        echo "SubCategoryID already exists in item table.\\n";
    }

    echo "Database migration complete.\\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
