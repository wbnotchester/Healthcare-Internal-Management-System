<?php
require "includes/db.php";
require "includes/auth.php";

/* verificar se já existe algum utilizador */
$stmt = $conn->query("SELECT COUNT(*) FROM users");
$userCount = $stmt->fetchColumn();

/* 🔐 se já existir user → exigir login admin */
if ($userCount > 0) {
    checkAuth();

    if ($_SESSION["user"]["role"] !== "admin") {
        die("Acesso negado");
    }
}

/* criar utilizador */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    /* 👇 primeiro user é sempre admin */
    if ($userCount == 0) {
        $role = "admin";
    } else {
        $role = $_POST["role"];
    }

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $role]);

    echo "Utilizador criado com sucesso!";
}
?>

<?php include "includes/header.php"; ?>

<div class="card">
    <h2>Criar Utilizador</h2>

    <form method="POST">
        <input name="username" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>

        <?php if ($userCount > 0): ?>
            <select name="role" required>
                <option value="doctor">Doctor</option>
                <option value="receptionist">Receptionist</option>
                <option value="admin">Admin</option>
            </select>
        <?php else: ?>
            <p>Primeiro utilizador será criado como <strong>Admin</strong></p>
        <?php endif; ?>

        <button type="submit">Criar</button>
    </form>
</div>

<?php include "includes/footer.php"; ?>