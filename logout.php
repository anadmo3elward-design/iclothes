<?php
session_start();
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['role']);
// Keep admin session if exists

header("Location: index.php");
exit;
?>