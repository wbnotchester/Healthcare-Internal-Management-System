<?php
require "includes/db.php";
require "includes/auth.php";

requireAuth();

$userId = $_SESSION['user']['id'];

/* buscar dados atuais */
$stmt = $conn->prepare("
    SELECT username, email, phone, mfa_enabled
    FROM users 
    WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);

    if ($email === '') {
        die("Email obrigatório");
    }

    $stmt = $conn->prepare("
        UPDATE users 
        SET email = ?, phone = ?
        WHERE id = ?
    ");

    $stmt->execute([$email, $phone, $userId]);

    echo "Dados atualizados com sucesso!";
}
?>

<?php include "includes/header.php"; ?>

<div class="card">
    <h2>Minha Conta</h2>

    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>

    <form method="POST">

        <label>Email *</label>
        <input name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Telefone</label>
        <input name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

        <button type="submit">Atualizar dados</button>
    </form>

    <h3>Alterar Password</h3>

    <form method="POST" action="change_password.php">
        <input name="current_password" type="password" placeholder="Password atual" required>
        <input name="new_password" type="password" placeholder="Nova password" required>

        <button type="submit">Alterar password</button>
    </form>

    <hr>

<h3>Autenticação Multifator (MFA)</h3>

<?php if ($user['mfa_enabled']): ?>

    <p style="color:green;">
        MFA ativado
    </p>

    <?php if ($_SESSION["user"]["role"] !== "Administrador"): ?>

        <form method="POST" action="disable_mfa.php">
            <button type="submit">
                Desativar MFA
            </button>
        </form>

            <?php endif; ?>

        <?php else: ?>

            <p style="color:red;">
                MFA desativado
            </p>

            <form method="POST" action="setup_mfa.php">

                <input type="hidden" name="enable_mfa" value="1">

                <button type="submit">
                    Ativar MFA
                </button>

            </form>

        <?php endif; ?>

</div>

<?php include "includes/footer.php"; ?>