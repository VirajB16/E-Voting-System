<?php
// Generate password hash for admin@Apsit
$password = 'admin@Apsit';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password: $password\n";
echo "Hash: $hash\n";
echo "Verification: " . (password_verify($password, $hash) ? 'VALID' : 'INVALID') . "\n";
?>
