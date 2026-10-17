<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("Run this script from the command line only.\n");

try {
    require_once __DIR__ . '/../app/bootstrap.php';
    // Kiểm tra kết nối DB
    $pdo = db();
} catch (Throwable $e) {
    echo "\n[LOI]: Khong the ket noi den Database!\n";
    echo "Hay dam bao XAMPP MySQL dang bat va chay lenh sau de khoi tao database truoc:\n";
    echo "   php database/setup.php\n\n";
    exit(1);
}

function prompt(string $label): string { echo $label; return trim((string) fgets(STDIN)); }
$username = prompt('Username: ');
$displayName = prompt('Display name: ');
if ($username === '' || $displayName === '') exit("Username and display name are required.\n");
$password = prompt('Password: ');
if (strlen($password) < 12) exit("Use a password with at least 12 characters.\n");

try {
    $stmt = db()->prepare('INSERT INTO admins (username, password_hash, display_name) VALUES (:username, :hash, :display_name)');
    $stmt->execute([':username' => $username, ':hash' => password_hash($password, PASSWORD_DEFAULT), ':display_name' => $displayName]);
    echo "Admin created successfully. Delete database/create_admin.php before deploying.\n";
} catch (PDOException $e) {
    echo "\n[LOI]: " . $e->getMessage() . "\n";
    exit(1);
}
