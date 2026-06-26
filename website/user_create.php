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
    $nome = trim($_POST["nome"] ?? '');
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');

    if ($username === '' || $email === '') {
        die("Todos os campos obrigatórios devem ser preenchidos.");
    }

    $tempPassword = bin2hex(random_bytes(16));
    $password = password_hash($tempPassword, PASSWORD_DEFAULT);
    $activationToken = generateActivationToken();

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
        INSERT INTO users (nome, username, email, password, role,must_change_password,activation_token)
        VALUES (?, ?, ?, ?, ?, 1, ?)
    ");

    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM users
        WHERE username = ?
        OR email = ?
    ");
    $stmt->execute([$username, $email]);

    if ($stmt->fetchColumn() > 0) {
        die("Já existe um utilizador com esse username ou email.");
    }

    $stmt->execute([
        $nome,
        $username,
        $email,
        $password,
        $role,
        $activationToken,
    ]);

    $newUserId = $conn->lastInsertId();

    auditLog(
        $conn,
        'USER',
        'USER_CREATED',
        $username,
        $newUserId,
        json_encode([
            'description' => 'Novo utilizador criado. Ativação pendente.',
            'name' => $nome,
            'email' => $email,
            'role' => $role
        ])
    );

    $createdUser = true;
}

function generateActivationToken(): string{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $token = '';
    for ($i = 0; $i < 12; $i++) {
        $token .= $chars[random_int(0, strlen($chars) - 1)];
        if (($i + 1) % 4 === 0 && $i !== 11) {
            $token .= '-';
        }
    }
    return $token;
}

?>

<?php include "includes/header.php"; ?>

<div class="container">
    <div class="card">
        <h2>Criar Utilizador</h2>

        <form method="POST">

            <label>Nome *</label><input name="nome" placeholder="Nome" required>

            <label>Username *</label><input name="username" placeholder="Username" required>

            <label>Email *</label><input name="email" type="email" placeholder="Email" required>


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
<?php if(isset($createdUser)): ?>

<div class="modal">
    <div class="modal-content">
        <h2>Utilizador criado com sucesso</h2>
        <p><strong>Username:</strong></p><p><?= htmlspecialchars($username) ?></p>
        <p><strong>Token de ativação:</strong></p>
        <h3><?= htmlspecialchars($activationToken) ?></h3>
        <p>Este token deverá ser entregue ao utilizador.</p>
        <div class="modal-actions">
            <button type="button" onclick="navigator.clipboard.writeText('<?= $activationToken ?>')">Copiar</button>
            <a class="btn-primary" href="user_create.php">Fechar</a>
        </div>
    </div>
</div>

<?php endif; ?>
<?php include "includes/footer.php"; ?>