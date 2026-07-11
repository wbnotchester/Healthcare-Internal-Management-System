<?php
$basePath = '/HIMS/website';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <title>HealthCare Internal Management System</title>
    <link rel="stylesheet" href="<?= $basePath ?>/style.css">
</head>
<body>

<header class="main-header">
    
    <div class="left">
        <a href="<?= $basePath ?>/dashboard.php" class="logo">
            <h2>HIMS</h2>
        </a>

        <nav>
            <a href="<?= $basePath ?>/dashboard.php">Home</a>
            <a href="<?= $basePath ?>/patients.php">Pacientes</a>

            <?php if (!empty($currentUser) && ($currentUser['role'] === 'Recepcionista' || $currentUser['role'] === 'Administrador')): ?>
            <a href="<?= $basePath ?>/consultations.php">Consultas</a>
            <?php endif; ?>

            <?php if (!empty($currentUser) && $currentUser['role'] === 'Doutor'): ?>
            <a href="<?= $basePath ?>/schedule.php">Horário</a>
            <?php endif; ?>

            <?php if (!empty($currentUser) && $currentUser['role'] === 'Administrador'): ?>
                <a href="<?= $basePath ?>/users.php">Utilizadores</a>
                <a href="<?= $basePath ?>/logs.php">Monitorização</a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="right">
        <?php if (!empty($currentUser)): ?>
            <div class="user-info">
            <span class="user-name"> 👤 <?= htmlspecialchars($currentUser['nome']) ?> </span>
            <span class="user-role"> <?= htmlspecialchars($currentUser['role']) ?> </span>
            </div>

            <a href="<?= $basePath ?>/account.php" class="account-btn">Minha Conta</a>
            <a href="<?= $basePath ?>/logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="<?= $basePath ?>/login.php" class="account-btn">Login</a>
        <?php endif; ?>
    </div>

</header>

<div class="container">