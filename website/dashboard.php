<?php
require "includes/auth.php";
checkAuth();

include "includes/header.php";
?>

<div class="card">
    <h2>Dashboard</h2>

    <p>Bem-vindo, <strong><?php echo $_SESSION["user"]["username"]; ?></strong></p>
    <p>Função: <?php echo $_SESSION["user"]["role"]; ?></p>

    <hr>

    <h3>Menu</h3>

    <ul>
        <li><a href="patients.php">👤 Gestão de Pacientes</a></li>

        <?php if ($_SESSION["user"]["role"] === "admin"): ?>
            <li><a href="admin.php">⚙️ Administração</a></li>
        <?php endif; ?>

        <li><a href="logout.php">🚪 Logout</a></li>
    </ul>
</div>

<?php include "includes/footer.php"; ?>