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
            <a href="consultations.php">Consultas</a>

            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'Administrador'): ?>
                <a href="create_user.php">Criar Utilizador</a>
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