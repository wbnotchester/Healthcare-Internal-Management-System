<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/audit.php";

requireAuth();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

$isSelf = $id == $_SESSION["user"]["id"]; /* Evitar que um utilizador se edite a si próprio */
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$isAdminTarget = $user["role"] === "Administrador";

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
    $username = $user["username"];
    $email    = trim($_POST["email"]);
    $phone    = trim($_POST["phone"]);

    if ($isSelf || $isAdminTarget) {
        $role = $user["role"];
    } else {
        $role = $_POST["role"];
    }

    if ($isSelf) {
        $isActive = $user["is_active"];
        $isLocked = $user["is_locked"];
    } else {
        $isActive = isset($_POST["is_active"]) ? 1 : 0;
        $isLocked = isset($_POST["is_locked"]) ? 1 : 0;
    }

    $forcePass = isset($_POST["must_change_password"]) ? 1 : 0;
    $must_resetMfa = isset($_POST["must_reset_mfa"]);

    if ($nome === "" || $username === "" || $email === "") {
        die("Todos os campos obrigatórios devem ser preenchidos.");
    }

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Token inválido.");
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
        $email,
        $phone,
        $role,
        $isActive,
        $isLocked,
        $forcePass,
        $id
    ]);

    if ($must_resetMfa) {
        $stmt = $conn->prepare("
            UPDATE users
            SET
                mfa_enabled = 0,
                mfa_secret = NULL,
                must_reset_mfa = 1,
                last_mfa_reset = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    /*Se foi desbloqueada limpa tentativas*/
    if (!$isLocked) {

        $stmt = $conn->prepare("
            UPDATE users
            SET failed_attempts = 0
            WHERE id = ?
        ");

        $stmt->execute([$id]);
    }

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

    if ($must_resetMfa) {
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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="filter-row two-cols">
                <div class="filter-field">
                    <label>Nome</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($user['nome']) ?>" disabled>
                </div>

                <div class="filter-field">
                    <label>Username</label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                </div>

            </div>

            <div class="filter-row two-cols">

                <div class="filter-field">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                </div>

                <div class="filter-field">
                    <label>Telefone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" disabled>
                </div>
            </div>

            <div class="filter-row two-cols">
                <div class="filter-field">
                    <label>Role</label>
                        <?php if ($isSelf || $isAdminTarget): ?>
                            <input type="text" value="<?= htmlspecialchars($user["role"]) ?>" disabled>
                        <?php else: ?>
                            <select name="role">
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= htmlspecialchars($r['role']) ?>" <?= $user['role'] == $r['role'] ? 'selected' : '' ?>> <?= htmlspecialchars($r['role']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                </div>

                <div class="filter-field">
                    <label>Estado</label>
                    <div class="checkbox-field">
                        <?php if ($isSelf): ?><input type="checkbox" <?= $user["is_active"] ? "checked" : "" ?> disabled>
                        <?php else: ?><input type="checkbox" name="is_active" value="1" <?= $user["is_active"] ? "checked" : "" ?>><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="filter-row two-cols">
                <div class="filter-field">
                    <label>Bloqueio</label>
                    <div class="checkbox-field">
                        <?php if ($isSelf): ?><input type="checkbox" <?= $user["is_locked"] ? "checked" : "" ?> disabled>
                            <?php else: ?><input type="checkbox" name="is_locked" value="1" <?= $user["is_locked"] ? "checked" : "" ?>><?php endif; ?>
                    </div>
                </div>

                <div class="filter-field">
                    <label>MFA</label>
                    <?php if ($user["must_reset_mfa"] === 0): ?>
                        <button class="btn-warning" type="submit" name="must_reset_mfa" value="1">Repor MFA</button>
                        <?php else: ?>
                        <div class="warning-box">Pedido de reposição pendente.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="filter-row full">
                <div class="filter-field">
                    <div class="checkbox-field">
                        <label>Password</label>
                        <input type="checkbox" name="must_change_password"  value="1" <?= $user["must_change_password"] ? "checked" : "" ?>>
                    </div>
                </div>
            </div>
            <button type="submit">Guardar Alterações</button>
        </form>

    </div>

</div>

<?php include "includes/footer.php"; ?>