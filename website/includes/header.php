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
            <?php if ($_SESSION['user']['role'] === 'Administrador'): ?>
                <a href="users.php">Utilizadores</a>
            <?php endif; ?>

        </nav>
    </div>

    <div class="right">
        <span class="user">
            <?= $_SESSION['user']['nome'] ?? '' ?>
        </span>
        <a href="account.php" class="account-btn">Minha Conta</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

</header>

<div class="container">