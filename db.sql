CREATE DATABASE IF NOT EXISTS `iclothes`;
USE `iclothes`;
-- 1. جدول المسؤولين (Admins)
CREATE TABLE `admins` (
  `AdminID` INT AUTO_INCREMENT PRIMARY KEY,
  `UserName` VARCHAR(100) NOT NULL,
  `Password` VARCHAR(255) NOT NULL,
  `LastLogin` DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- 2. جدول المستخدمين (Users)
CREATE TABLE `users` (
  `UserID` INT AUTO_INCREMENT PRIMARY KEY,
  `UserName` VARCHAR(100) NOT NULL,
  `Password` VARCHAR(255) NOT NULL,
  `Name` VARCHAR(255) NOT NULL,
  `Mobile` VARCHAR(20),
  `Email` VARCHAR(150) UNIQUE,    
  `LastLogin` DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- 3. جدول التصنيفات (Category)
CREATE TABLE `category` (
  `CategoryID` INT AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(150) NOT NULL,
  `Tags` VARCHAR(255),
  `CreatedBy` INT,
  FOREIGN KEY (`CreatedBy`) REFERENCES `admins`(`AdminID`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. جدول الأقسام الفرعية (Sub-Category)
CREATE TABLE `sub_category` (
  `SubCategoryID` INT AUTO_INCREMENT PRIMARY KEY,
  `CategoryID` INT NOT NULL,
  `Name` VARCHAR(150) NOT NULL,
  `CreatedBy` INT,
  FOREIGN KEY (`CategoryID`) REFERENCES `category`(`CategoryID`) ON DELETE CASCADE,
  FOREIGN KEY (`CreatedBy`) REFERENCES `admins`(`AdminID`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. جدول المنتجات (Item)
CREATE TABLE `item` (
  `ItemID` INT AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(255) NOT NULL,
  `CategoryID` INT,
  `SubCategoryID` INT DEFAULT NULL,
  `Description` TEXT,
  `Tags` VARCHAR(255),
  `CreatedBy` INT,
  FOREIGN KEY (`CategoryID`) REFERENCES `category`(`CategoryID`) ON DELETE CASCADE,
  FOREIGN KEY (`SubCategoryID`) REFERENCES `sub_category`(`SubCategoryID`) ON DELETE SET NULL,
  FOREIGN KEY (`CreatedBy`) REFERENCES `admins`(`AdminID`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. جدول الصور (Image)
CREATE TABLE `image` (
  `ImageID` INT AUTO_INCREMENT PRIMARY KEY,
  `FileName` VARCHAR(255) NOT NULL,
  `ItemID` INT,
  FOREIGN KEY (`ItemID`) REFERENCES `item`(`ItemID`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. جدول الأحجام (Size)
CREATE TABLE `size` (
  `SizeID` INT AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(50) NOT NULL,
  `Tags` VARCHAR(255)
) ENGINE=InnoDB;

-- 7. جدول الألوان (Color)
CREATE TABLE `color` (
  `ColorID` INT AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(50) NOT NULL,
  `Tags` VARCHAR(255)
) ENGINE=InnoDB;

-- 8. جدول الكوبونات (Copouns)
CREATE TABLE `copouns` (
  `CouponID` INT AUTO_INCREMENT PRIMARY KEY,
  `CouponCode` VARCHAR(50) UNIQUE NOT NULL,
  `DiscountAmount` DECIMAL(10, 2) NOT NULL,
  `AddedBy` INT,
  FOREIGN KEY (`AddedBy`) REFERENCES `admins`(`AdminID`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 9. جدول المخزن (Inventory)
CREATE TABLE `inventory` (
  `InventoryID` INT AUTO_INCREMENT PRIMARY KEY,
  `ItemID` INT,
  `SizeID` INT,
  `ColorID` INT,
  `Amount` INT DEFAULT 0,
  `Price` DECIMAL(10, 2) NOT NULL,
  `AddedBy` INT,
  `AddedOn` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ItemID`) REFERENCES `item`(`ItemID`) ON DELETE CASCADE,
  FOREIGN KEY (`SizeID`) REFERENCES `size`(`SizeID`) ON DELETE CASCADE,
  FOREIGN KEY (`ColorID`) REFERENCES `color`(`ColorID`) ON DELETE CASCADE,
  FOREIGN KEY (`AddedBy`) REFERENCES `admins`(`AdminID`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 10. جدول الطلبات (Order)
CREATE TABLE `order` (
  `OrderID` INT AUTO_INCREMENT PRIMARY KEY,
  `UserID` INT,
  `CouponID` INT,
  `OrderDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `OrderStatus` VARCHAR(50),
  `ProcessedBy` INT,
  `TotalPrice` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE CASCADE,
  FOREIGN KEY (`CouponID`) REFERENCES `copouns`(`CouponID`) ON DELETE SET NULL,
  FOREIGN KEY (`ProcessedBy`) REFERENCES `admins`(`AdminID`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 11. تفاصيل الطلب (OrderDetail)
CREATE TABLE `order_detail` (
  `OrderDetailID` INT AUTO_INCREMENT PRIMARY KEY,
  `OrderID` INT,
  `InventoryID` INT,
  `Amount` INT NOT NULL,
  `ItemTotalPrice` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`OrderID`) REFERENCES `order`(`OrderID`) ON DELETE CASCADE,
  FOREIGN KEY (`InventoryID`) REFERENCES `inventory`(`InventoryID`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. جدول السلة (Cart)
CREATE TABLE `cart` (
  `CartID` INT AUTO_INCREMENT PRIMARY KEY,
  `InventoryID` INT,
  `UserID` INT,
  `Amount` INT NOT NULL,
  `AddedOn` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`InventoryID`) REFERENCES `inventory`(`InventoryID`) ON DELETE CASCADE,
  FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB;
