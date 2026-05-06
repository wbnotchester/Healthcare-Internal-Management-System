<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkMFA() {
    if (!isset($_SESSION['mfa_passed']) || $_SESSION['mfa_passed'] !== true) {
        header("Location: login.php");
        exit();
    }
}

function requireAuth() {
    if (!isset($_SESSION['user']) || !isset($_SESSION['mfa_passed']) || $_SESSION['mfa_passed'] !== true) {
        header("Location: login.php");
        exit();
    }
}

function logout() {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}