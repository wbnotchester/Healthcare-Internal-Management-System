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
    'MFA',
    'DISABLE_MFA',
    $_SESSION['user'],
    $userId,
    'Usuário desativou MFA',
    'WARNING'
);

header("Location: account.php");
exit();