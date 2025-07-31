<?php
$password_to_hash = "company"; 
$hashed_password = password_hash($password_to_hash, PASSWORD_DEFAULT);
echo "Password: " . $password_to_hash . "<br>";
echo "Hashed Password: " . $hashed_password . "<br>";
?>