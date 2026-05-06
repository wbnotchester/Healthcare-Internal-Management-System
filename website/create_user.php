<?php
require "includes/db.php";
require "includes/auth.php";

/* verificar se já existe algum utilizador */
$stmt = $conn->query("SELECT COUNT(*) FROM users");
$userCount = $stmt->fetchColumn();

if ($userCount > 0) {
    requireAuth();

    if ($_SESSION["user"]["role"] !== "admin") {
        die("Acesso negado");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"] ?? '');
    $passwordInput = $_POST["password"] ?? '';

    if (empty($username) || empty($passwordInput)) {
        die("Username e password são obrigatórios.");
    }

    $password = password_hash($passwordInput, PASSWORD_DEFAULT);

    // primeiro utilizador = admin
    if ($userCount == 0) {
        $role = "admin";
    } else {
        $allowedRoles = ["doctor", "receptionist", "admin"];
        $role = $_POST["role"] ?? '';

        if (!in_array($role, $allowedRoles)) {
            die("Role inválido.");
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO users (username, password, role)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $username,
        $password,
        $role
    ]);

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