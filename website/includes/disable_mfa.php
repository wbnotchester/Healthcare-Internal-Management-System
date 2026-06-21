<?php
require "db.php";
require "auth.php";

requireAuth();

$userId = $_SESSION['user']['id'];

$conn->prepare("
    UPDATE users
    SET mfa_enabled = 0,
        mfa_secret = NULL
    WHERE id = ?
")->execute([$userId]);

header("Location: account.php");
exit();