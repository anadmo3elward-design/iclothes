<?php
require_once 'config.php';

echo "<h2>تجهيز قاعدة البيانات...</h2>";

try {
    // 1. Create default Admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE UserName = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO admins (UserName, Password) VALUES ('admin', '$password')");
        echo "تم إنشاء حساب المدير (admin/admin123)<br>";
    }

    $adminId = $pdo->lastInsertId() ?: 1;

    // 2. Create Categories
    $categories = ['رجالي', 'نسائي', 'أطفال', 'اكسسوارات'];
    foreach ($categories as $cat) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM category WHERE Name = ?");
        $stmt->execute([$cat]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO category (Name, CreatedBy) VALUES (?, ?)");
            $stmt->execute([$cat, $adminId]);
            echo "تم إنشاء القسم: $cat<br>";
        }
    }

    // 3. Create Sizes
    $sizes = ['S', 'M', 'L', 'XL'];
    foreach ($sizes as $size) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM size WHERE Name = ?");
        $stmt->execute([$size]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO size (Name) VALUES ('$size')");
            echo "تم إنشاء الحجم: $size<br>";
        }
    }

    // 4. Create Colors
    $colors = ['أحمر', 'أزرق', 'أسود', 'أبيض', 'أخضر'];
    foreach ($colors as $color) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM color WHERE Name = ?");
        $stmt->execute([$color]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO color (Name) VALUES ('$color')");
            echo "تم إنشاء اللون: $color<br>";
        }
    }

    // 5. Create Items (Dummy Data)
    $items = [
        ['Name' => 'قميص كلاسيكي', 'Cat' => 'رجالي', 'Price' => 70.00, 'Desc' => 'قميص قطني مريح وأنيق.'],
        ['Name' => 'فستان صيفي', 'Cat' => 'نسائي', 'Price' => 120.00, 'Desc' => 'فستان خفيف ومناسب للصيف.'],
        ['Name' => 'جينز', 'Cat' => 'رجالي', 'Price' => 150.00, 'Desc' => 'بنطال جينز متين.'],
        ['Name' => 'سترة أطفال', 'Cat' => 'أطفال', 'Price' => 90.00, 'Desc' => 'سترة دافئة ومريحة للأطفال.'],
    ];

    foreach ($items as $item) {
        // Get Cat ID
        $stmt = $pdo->prepare("SELECT CategoryID FROM category WHERE Name = ?");
        $stmt->execute([$item['Cat']]);
        $catId = $stmt->fetchColumn();

        // Check if item exists
        $stmt = $pdo->prepare("SELECT ItemID FROM item WHERE Name = ?");
        $stmt->execute([$item['Name']]);
        $itemId = $stmt->fetchColumn();

        if (!$itemId && $catId) {
            $stmt = $pdo->prepare("INSERT INTO item (Name, CategoryID, Description, CreatedBy) VALUES (?, ?, ?, ?)");
            $stmt->execute([$item['Name'], $catId, $item['Desc'], $adminId]);
            $itemId = $pdo->lastInsertId();
            echo "تم إنشاء المنتج: {$item['Name']}<br>";

            // Create Inventory for this item (All sizes/colors for simplicity)
            $sizeStmt = $pdo->query("SELECT SizeID FROM size LIMIT 2");
            $sizes = $sizeStmt->fetchAll(PDO::FETCH_COLUMN);

            $colorStmt = $pdo->query("SELECT ColorID FROM color LIMIT 2");
            $colors = $colorStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($sizes as $sId) {
                foreach ($colors as $cId) {
                    $stmt = $pdo->prepare("INSERT INTO inventory (ItemID, SizeID, ColorID, Amount, Price, AddedBy) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$itemId, $sId, $cId, 50, $item['Price'], $adminId]);
                }
            }
            echo " - تم إضافة المخزون للمنتج.<br>";
        }
    }

    echo "<h3>تم تجهيز البيانات بنجاح!</h3>";
    echo "<a href='index.php'>اذهب إلى الرئيسية</a>";

} catch (PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?>