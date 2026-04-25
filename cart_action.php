<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect to login if trying to add to cart without session
    header("Location: login.php?redirect=back");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];
    $userId = $_SESSION['user_id'];

    if ($action == 'add') {
        $inventoryId = $_POST['inventory_id'];
        $amount = (int) $_POST['amount'];

        if ($amount <= 0) {
            header("Location: shop.php?error=" . urlencode("الكمية يجب أن تكون أكبر من 0"));
            exit;
        }

        // Check if already in cart
        $stmt = $pdo->prepare("SELECT CartID, Amount FROM cart WHERE UserID = ? AND InventoryID = ?");
        $stmt->execute([$userId, $inventoryId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $newAmount = $existing['Amount'] + $amount;
            $update = $pdo->prepare("UPDATE cart SET Amount = ? WHERE CartID = ?");
            $update->execute([$newAmount, $existing['CartID']]);
        } else {
            // Insert
            $insert = $pdo->prepare("INSERT INTO cart (UserID, InventoryID, Amount) VALUES (?, ?, ?)");
            $insert->execute([$userId, $inventoryId, $amount]);
        }

        header("Location: cart.php");
        exit;

    } elseif ($action == 'remove') {
        $cartId = $_POST['cart_id'];
        $del = $pdo->prepare("DELETE FROM cart WHERE CartID = ? AND UserID = ?");
        $del->execute([$cartId, $userId]);
        header("Location: cart.php");
        exit;
    } elseif ($action == 'update_qty') {
        // Implement update logic if needed
    }
}
?>