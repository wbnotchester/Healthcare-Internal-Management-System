<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Meditrust Clinical Manager</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body>

<header class="main-header">
    
    <div class="left">
        <a href="dashboard.php" class="logo">
            <h2>Meditrust</h2>
        </a>

        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="patients.php">Pacientes</a>
            <a href="consultations.php">Consultas</a>
            <a href="create_consultation.php">Nova Consulta</a>

            <?php if (!empty($user) && $user['role'] === 'Administrador'): ?>
                <a href="users.php">Utilizadores</a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="right">
        <?php if (!empty($user)): ?>
            <span class="user">
                <?= htmlspecialchars($user['nome']) ?>
            </span>

            <a href="account.php" class="account-btn">Minha Conta</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="login.php" class="account-btn">Login</a>
        <?php endif; ?>
    </div>

</header>

<div class="container">