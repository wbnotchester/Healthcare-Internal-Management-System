<?php
session_start();

require "includes/db.php";
require "includes/audit.php";

if (isset($_SESSION['user'])) {
    auditLog(
        $conn,
        'LOGIN',
        'LOGOUT',
        null,
        null,
        json_encode([
            'description' => 'Terminou sessão'
        ]),
        'INFO',
        $_SESSION['user']['id'],
        $_SESSION['user']['username']
    );
}

session_destroy();

header("Location: login.php");
exit();