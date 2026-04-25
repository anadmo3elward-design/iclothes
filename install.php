<?php
// install.php

// Database configuration (Should match config.php or be standalone)
$host = 'localhost';
$username = 'root';
$password = '';

echo "<h1>IClothes Database Installer</h1>";

try {
    // 1. Connect to MySQL Server (No Database selected yet)
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p>Connected to MySQL server successfully.</p>";

    // 2. Read the SQL file
    $sqlFile = 'db.sql';
    if (!file_exists($sqlFile)) {
        die("<p style='color:red'>Error: File '$sqlFile' not found.</p>");
    }

    $sql = file_get_contents($sqlFile);

    // 3. Execute the SQL commands
    // We expect the SQL file to contain CREATE DATABASE and USE statements as added by the user
    // or we can force it here if missing, but user said they added it.

    // Check if CREATE DATABASE is in the file, if not, we might want to add it or warn.
    // But assuming user acted on the previous step:

    $pdo->exec($sql);

    echo "<p style='color:green'>Database setup successfully executed!</p>";
    echo "<p>Tables created. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='db_seed.php'>Seed Dummy Data</a> (Recommended)</li>";
    echo "<li><a href='index.php'>Go to Home Page</a></li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color:red'>DB Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>