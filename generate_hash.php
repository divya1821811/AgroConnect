<?php
$password = ''; // <<< CHANGE THIS to your desired admin password (e.g., adminpass123)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Your desired password (plaintext) is: " . $password . "<br>";
echo "Your NEW, proper hashed password is: " . $hashed_password;
?>