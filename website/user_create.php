<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/audit.php";

/* verificar se já existe algum utilizador */
$stmt = $conn->query("SELECT COUNT(*) FROM users");
$userCount = $stmt->fetchColumn();

/* proteção de acesso */
if ($userCount > 0) {
    requireAuth();

    if ($_SESSION["user"]["role"] !== "Administrador") {
        die("Acesso negado");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST["Nome"] ?? '');
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $passwordInput = $_POST["password"] ?? '';

    if ($username === '' || $email === '' || $passwordInput === '') {
        die("Todos os campos obrigatórios devem ser preenchidos.");
    }

    $password = password_hash($passwordInput, PASSWORD_DEFAULT);

    if ($userCount == 0) {
        $role = "Administrador";
    } else {
        $role = $_POST["role"] ?? '';

        $stmt = $conn->prepare("SELECT COUNT(*) FROM permissions WHERE role = ?");
        $stmt->execute([$role]);

        if ($stmt->fetchColumn() == 0) {
            die("Role inválido.");
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO users (nome, username, email, password, role)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $nome,
        $username,
        $email,
        $password,
        $role
    ]);

    $newUserId = $conn->lastInsertId();

    auditLog(
        $conn,
        'USER',
        'USER_CREATED',
        $username,
        $newUserId,
        json_encode([
            'description' => 'Novo utilizador criado',
            'name' => $nome,
            'email' => $email,
            'role' => $role
        ])
    );

    echo "Utilizador criado com sucesso!";
}
?>

<?php include "includes/header.php"; ?>

<div class="container">
    <div class="card">
        <h2>Criar Utilizador</h2>

        <form method="POST">

            <label>Nome *</label><input name="Nome" placeholder="Nome" required>

            <label>Username *</label><input name="username" placeholder="Username" required>

            <label>Email *</label><input name="email" type="email" placeholder="Email" required>

            <label>Password *</label><input name="password" type="password" placeholder="Password" required>

            <?php if ($userCount > 0): ?>
                <label>Role *</label>
                <select name="role" required>
                    <?php
                    $roles = $conn->query("SELECT role FROM permissions ORDER BY role");

                    foreach ($roles as $r):
                    ?>
                        <option value="<?= htmlspecialchars($r["role"]) ?>">
                            <?= ucfirst($r["role"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <p><strong>Primeiro utilizador será Admin automaticamente</strong></p>
                <input type="hidden" name="role" value="Administrador">
            <?php endif; ?>

            <button type="submit">Criar utilizador</button>
        </form>
    </div>
</div>

<?php include "includes/footer.php"; ?>