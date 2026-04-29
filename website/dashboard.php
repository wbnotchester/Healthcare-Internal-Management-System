<?php
require "includes/auth.php";
checkAuth();
?>

<h1>Dashboard</h1>

<p>Bem-vindo <?php echo $_SESSION["user"]["username"]; ?></p>

<a href="logout.php">Logout</a>