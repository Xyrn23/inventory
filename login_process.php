<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();

try {
    $db = new PDO("sqlite:C:\\Users\\xyrn\\Documents\\inventory\\inventory.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user'] = $user['username'];
    header("Location: dashboard.php");
    exit();
} else {
    echo "Invalid credentials. <a href='login.php'>Try again</a>";
}
