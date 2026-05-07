<?php
session_start();

require "includes/db.php";
require "includes/auth.php";
require "includes/mfa.php";
require_once "includes/GoogleAuthenticator.php";

/* validar acesso */
if (
    !isset($_SESSION['mfa_setup_user_id']) &&
    !isset($_GET['id']) &&
    !isset($_POST['enable_mfa'])
) {
    header("Location: login.php");
    exit();
}

$ga = new PHPGangsta_GoogleAuthenticator();

/* 🔹 detectar modo */

// 👉 primeiro login obrigatório
if (isset($_SESSION['mfa_setup_user_id'])) {

    $user_id = $_SESSION['mfa_setup_user_id'];
    $isSelfSetup = true;

// 👉 admin configurar utilizador
} elseif (isset($_GET['id'])) {

    requireAuth();

    if ($_SESSION["user"]["role"] !== "Administrador") {
        die("Acesso negado");
    }

    $user_id = $_GET['id'];
    $isSelfSetup = false;

// 👉 utilizador ativar MFA manualmente
} elseif (isset($_POST['enable_mfa'])) {

    requireAuth();

    $user_id = $_SESSION['user']['id'];
    $isSelfSetup = true;

} else {
    die("Acesso inválido");
}

/* buscar user */
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User não existe");
}

/* gerar secret se necessário */
if (!$user['mfa_secret']) {

    $secret = $ga->createSecret();

    $conn->prepare("
        UPDATE users 
        SET mfa_secret = ?
        WHERE id = ?
    ")->execute([$secret, $user_id]);

} else {
    $secret = $user['mfa_secret'];
}

/* gerar QR Code */
$qr = $ga->getQRCodeGoogleUrl(
    "Meditrust:" . $user['username'],
    $secret
);

/* permitir ignorar MFA para users normais */
if (
    isset($_POST['skip_mfa']) &&
    $isSelfSetup &&
    $user['role'] !== 'Administrador'
) {

    unset($_SESSION['mfa_setup_user_id']);

    $_SESSION['user'] = $user;

    $conn->prepare("
        UPDATE users
        SET last_login = NOW()
        WHERE id = ?
    ")->execute([$user_id]);

    header("Location: dashboard.php");
    exit();
}

/* confirmar código MFA */
if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    isset($_POST["code"])
) {

    $code = trim($_POST["code"]);

    if ($ga->verifyCode($secret, $code, 2)) {

        $conn->prepare("
            UPDATE users 
            SET mfa_enabled = 1,
                last_login = NOW()
            WHERE id = ?
        ")->execute([$user_id]);

        /* atualizar sessão */
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $updatedUser = $stmt->fetch();

        $_SESSION['user'] = $updatedUser;

        unset($_SESSION['mfa_setup_user_id']);

        header("Location: dashboard.php");
        exit();

    } else {
        $error = "Código inválido";
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2>
        Ativar MFA para 
        <?= htmlspecialchars($user['username']) ?>
    </h2>

    <?php if (
        $isSelfSetup &&
        $user['role'] === 'Administrador'
    ): ?>

        <p>
            <strong>
                Configuração obrigatória de segurança
            </strong>
        </p>

    <?php endif; ?>

    <p>
        1. Faz scan deste QR Code no Google Authenticator:
    </p>

    <img src="<?= $qr ?>">

    <p>
        2. Insere o código da app:
    </p>

    <form method="POST">

        <?php if (!isset($_SESSION['mfa_setup_user_id'])): ?>
            <input type="hidden" name="enable_mfa" value="1">
        <?php endif; ?>

        <input
            name="code"
            placeholder="000000"
            required
        >

        <button type="submit">
            Ativar MFA
        </button>

    </form>

    <?php if (
        $isSelfSetup &&
        $user['role'] !== 'Administrador' &&
        isset($_SESSION['mfa_setup_user_id'])
    ): ?>

        <form method="POST">

            <button type="submit" name="skip_mfa">
                Ignorar por agora
            </button>

        </form>

    <?php endif; ?>

    <?php if (isset($error)): ?>
        <p><?= $error ?></p>
    <?php endif; ?>

</div>

<?php include "includes/footer.php"; ?>