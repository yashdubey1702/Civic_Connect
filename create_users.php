```php
<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection(); // mysqli

// Generate proper password hashes
$admin_password   = 'admin123';
$citizen_password = 'citizen123';

$admin_hash   = password_hash($admin_password, PASSWORD_DEFAULT);
$citizen_hash = password_hash($citizen_password, PASSWORD_DEFAULT);

echo "Admin hash: " . $admin_hash . "<br>";
echo "Citizen hash: " . $citizen_hash . "<br>";

// Delete existing test users
$deleteSql = "DELETE FROM users WHERE email IN (?, ?)";
$deleteStmt = $db->prepare($deleteSql);
$email1 = 'admin@municipal.gov.ph';
$email2 = 'citizen@example.com';
$deleteStmt->bind_param("ss", $email1, $email2);
$deleteStmt->execute();
$deleteStmt->close();

// Insert admin
$adminSql = "INSERT INTO users (email, password_hash, full_name, user_type, is_active)
             VALUES (?, ?, ?, 'admin', 1)";
$adminStmt = $db->prepare($adminSql);
$adminEmail = 'admin@municipal.gov.ph';
$adminName  = 'System Administrator';
$adminStmt->bind_param("sss", $adminEmail, $admin_hash, $adminName);
$adminStmt->execute();
$adminStmt->close();

// Insert citizen
$citizenSql = "INSERT INTO users (email, password_hash, full_name, user_type, is_active)
               VALUES (?, ?, ?, 'citizen', 1)";
$citizenStmt = $db->prepare($citizenSql);
$citizenEmail = 'citizen@example.com';
$citizenName  = 'Juan Dela Cruz';
$citizenStmt->bind_param("sss", $citizenEmail, $citizen_hash, $citizenName);
$citizenStmt->execute();
$citizenStmt->close();

echo "Users created successfully!<br>";
echo "Admin: admin@municipal.gov.ph / admin123<br>";
echo "Citizen: citizen@example.com / citizen123<br>";
?>

