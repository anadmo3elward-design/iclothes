<?php
session_start();
// Unset Admin Session Variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_logged_in']);

// Optional: destroy session if user is not logged in either?
// For now, just unset admin vars to allow user session to persist if it exists.

header("Location: ../login.php");
exit;
?>