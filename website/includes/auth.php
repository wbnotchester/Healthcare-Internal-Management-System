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

function requireRole($roles) {

    if (!isset($_SESSION["user"])) {
        header("Location: login.php");
        exit();
    }

    $userRole = $_SESSION["user"]["role"];

    if (!in_array($userRole, (array)$roles)) {
        die("Acesso negado");
    }
}