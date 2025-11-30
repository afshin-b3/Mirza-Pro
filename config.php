<?php
$dbname = '{database_name}';
$usernamedb = '{username_db}';
$passworddb = '{password_db}';
$connect = mysqli_connect("localhost", $usernamedb, $passworddb, $dbname);
if ($connect->connect_error) { die("error" . $connect->connect_error); }
mysqli_set_charset($connect, "utf8mb4");
$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, ];
$dsn = "mysql:host=localhost;dbname=$dbname;charset=utf8mb4";
try { $pdo = new PDO($dsn, $usernamedb, $passworddb, $options); } catch (\PDOException $e) { error_log("Database connection failed: " . $e->getMessage()); }
$APIKEY = '{API_KEY}';
$adminnumber = '{admin_number}';
$domainhosts = '{domain_name}';
$usernamebot = '{username_bot}';

$new_marzban = true;

// Application-wide pepper and subscription token secret.
// Replace the placeholder values with strong random strings in production.
if (!defined('APP_PEPPER')) {
    define('APP_PEPPER', getenv('APP_PEPPER') ?: 'change_this_pepper_to_random_value');
}
if (!defined('SUB_TOKEN_SECRET')) {
    define('SUB_TOKEN_SECRET', getenv('SUB_TOKEN_SECRET') ?: 'change_this_sub_token_secret');
}
if (!defined('SUB_TOKEN_TTL')) {
    define('SUB_TOKEN_TTL', 86400); // 24 hours
}
?>
