<?php
require "includes/db.php";
require "includes/GoogleAuthenticator.php";

$ga = new PHPGangsta_GoogleAuthenticator();

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(["admin"]);
$user = $stmt->fetch();

$secret = $user['mfa_secret'];

echo "<h2>MFA DEBUG</h2>";
echo "Secret: $secret <br>";
echo "Code atual: <b>" . $ga->getCode($secret) . "</b><br>";
?>