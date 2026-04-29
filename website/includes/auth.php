<?php
session_start();

function checkAuth() {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }
}

function logEvent($message) {
    file_put_contents("logs/logs.txt", $message . "\n", FILE_APPEND);
}
?>