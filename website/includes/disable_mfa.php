<?php
require "db.php";
require "auth.php";
require "audit.php";

requireAuth();

$userId = $_SESSION['user']['id'];

$conn->prepare("
    UPDATE users
    SET mfa_enabled = 0,
        mfa_secret = NULL
    WHERE id = ?
")->execute([$userId]);

auditLog(
    $conn,
    'DISABLE_MFA',
    'USER',
    $userId,
    'User disabled MFA',
    'WARNING'
);

header("Location: account.php");
exit();