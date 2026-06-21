<?php
session_start();

require "includes/audit.php";

if (isset($_SESSION['user'])) {
    auditLog(
        $conn,
        "LOGOUT",
        "user",
        $_SESSION['user']['id'],
        "Terminou sessão"
    );
}

session_destroy();

header("Location: login.php");
exit();