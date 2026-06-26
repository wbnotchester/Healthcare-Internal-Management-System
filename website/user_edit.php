<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/audit.php";

requireAuth();

if ($_SESSION["user"]["role"] !== "Administrador") {
    die("Acesso negado.");
}

$id = $_GET["id"] ?? null;

if (!$id || !ctype_digit($id)) {
    die("Utilizador inválido.");
}

/* Buscar utilizador */
$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE id = ?
");
$stmt->execute([$id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilizador não encontrado.");
}

/* Buscar roles */
$roles = $conn->query("
    SELECT role
    FROM permissions
    ORDER BY role
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome     = trim($_POST["nome"]);
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"]);
    $phone    = trim($_POST["phone"]);

    $role     = $_POST["role"];

    $isActive = (int)$_POST["is_active"];
    $isLocked = (int)$_POST["is_locked"];

    $forcePass = (int)$_POST["must_change_password"];
    $resetMfa  = (int)$_POST["reset_mfa"];

    if ($nome === "" || $username === "" || $email === "") {
        die("Todos os campos obrigatórios devem ser preenchidos.");
    }

    /* Evitar usernames duplicados */
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM users
        WHERE (username = ? OR email = ?)
        AND id <> ?
    ");

    $stmt->execute([$username, $email, $id]);

    if ($stmt->fetchColumn() > 0) {
        die("Já existe um utilizador com esse username ou email.");
    }

    /* Atualizar dados */
    $stmt = $conn->prepare("
        UPDATE users
        SET
            nome = ?,
            username = ?,
            email = ?,
            phone = ?,
            role = ?,
            is_active = ?,
            is_locked = ?,
            must_change_password = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $nome,
        $username,
        $email,
        $phone,
        $role,
        $isActive,
        $isLocked,
        $forcePass,
        $id
    ]);

    /* Reset MFA */
    if ($resetMfa) {

        $stmt = $conn->prepare("
            UPDATE users
            SET
                mfa_enabled = 0,
                mfa_secret = NULL
            WHERE id = ?
        ");

        $stmt->execute([$id]);
    }

    /* Se foi desbloqueada limpa tentativas */
    if (!$isLocked) {

        $stmt = $conn->prepare("
            UPDATE users
            SET failed_attempts = 0
            WHERE id = ?
        ");

        $stmt->execute([$id]);
    }

    /* Audit Log */
    $changes = [];

    foreach ([
        "nome",
        "username",
        "email",
        "phone",
        "role",
        "is_active",
        "is_locked",
        "must_change_password"
    ] as $field) {

        $newValue = match ($field) {
            "nome" => $nome,
            "username" => $username,
            "email" => $email,
            "phone" => $phone,
            "role" => $role,
            "is_active" => $isActive,
            "is_locked" => $isLocked,
            "must_change_password" => $forcePass
        };

        if ((string)$user[$field] !== (string)$newValue) {

            $changes[$field] = [
                "old" => $user[$field],
                "new" => $newValue
            ];
        }
    }

    if ($resetMfa) {
        $changes["mfa"] = "Reposto pelo administrador";
    }

    if (!empty($changes)) {

        auditLog(
            $conn,
            "USER",
            "USER_UPDATED",
            $username,
            $id,
            json_encode($changes)
        );
    }

    header("Location: users.php?updated=1");
    exit();
}
?>

<?php include "includes/header.php"; ?>

<div class="container">
    <div class="page-header">
        <h2>Editar Utilizador</h2><a href="users.php" class="btn-primary">← Voltar</a>
    </div>

    <div class="card">

        <form method="POST">
            <div class="filter-row two-cols">
                <div class="filter-field">
                    <label>Nome</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($user['nome']) ?>" required>
                </div>

                <div class="filter-field">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

            </div>

            <div class="filter-row two-cols">

                <div class="filter-field">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="filter-field">
                    <label>Telefone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                </div>
            </div>

            <div class="filter-row two-cols">
                <div class="filter-field">
                    <label>Role</label>
                    <select name="role">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= htmlspecialchars($r['role']) ?>" <?= $user['role'] == $r['role'] ? 'selected' : '' ?>> <?= htmlspecialchars($r['role']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-field">
                    <label>Estado</label>
                    <select name="is_active">
                        <option value="1" <?= (int)$user['is_active'] ? 'selected' : '' ?>>Ativa</option>
                        <option value="0" <?= (int)!$user['is_active'] ? 'selected' : '' ?>>Desativada</option>
                    </select>
                </div>
            </div>

            <div class="filter-row two-cols">
                <div class="filter-field">
                    <label>Bloqueio</label>
                    <select name="is_locked">
                        <option value="0" <?= (int)!$user['is_locked'] ? 'selected' : '' ?>>Desbloqueada</option>
                        <option value="1" <?= (int)$user['is_locked'] ? 'selected' : '' ?>>Bloqueada</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label>MFA</label>
                    <select name="reset_mfa">
                        <option value="0">Manter</option>
                        <option value="1">Repor MFA</option>
                    </select>
                </div>
            </div>

            <div class="filter-row full">
                <div class="filter-field">
                    <label>Password</label>
                    <select name="must_change_password">
                        <option value="0" <?= (int)!$user['must_change_password'] ? 'selected' : '' ?>>Não alterar</option>
                        <option value="1" <?= (int)$user['must_change_password'] ? 'selected' : '' ?>>Forçar nova password no próximo acesso</option>
                    </select>

                </div>

            </div>
            <button type="submit">Guardar Alterações</button>
        </form>

    </div>

</div>

<?php include "includes/footer.php"; ?>