<?php
session_start();

require "includes/db.php";
require "includes/auth.php";
require "includes/mfa.php";
require_once "includes/GoogleAuthenticator.php";

$ga = new PHPGangsta_GoogleAuthenticator();

/* 🔹 validar acesso base */
if (
    !isset($_SESSION['mfa_setup_user_id']) &&
    !isset($_GET['id']) &&
    !isset($_POST['enable_mfa'])
) {
    header("Location: login.php");
    exit();
}

/* 🔹 modo de operação */
$isSelfSetup = false;
$user_id = null;

/* 👉 primeiro login obrigatório */
if (isset($_SESSION['mfa_setup_user_id'])) {

    $user_id = (int) $_SESSION['mfa_setup_user_id'];
    $isSelfSetup = true;

/* 👉 admin configurar utilizador */
} elseif (isset($_GET['id'])) {

    requireAuth();

    if (($_SESSION["user"]["role"] ?? null) !== "Administrador") {
        die("Acesso negado");
    }

    $user_id = (int) $_GET['id'];

/* 👉 utilizador ativar MFA manualmente */
} elseif (isset($_POST['enable_mfa'])) {

    requireAuth();

    $user_id = (int) ($_SESSION['user']['id'] ?? 0);
    $isSelfSetup = true;

} else {
    die("Acesso inválido");
}

if (!$user_id) {
    die("ID inválido");
}

/* 🔹 buscar utilizador */
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

/* 🔥 proteção crítica */
if (!$user || !is_array($user)) {
    die("Utilizador não encontrado");
}

/* 🔹 gerar secret se necessário */
if (empty($user['mfa_secret'])) {

    $secret = $ga->createSecret();

    $conn->prepare("
        UPDATE users 
        SET mfa_secret = ?
        WHERE id = ?
    ")->execute([$secret, $user_id]);

} else {
    $secret = $user['mfa_secret'];
}

/* 🔹 gerar QR code */
$qr = $ga->getQRCodeGoogleUrl(
    "Meditrust:" . ($user['username'] ?? 'user'),
    $secret
);

/* 🔹 skip MFA */
if (
    isset($_POST['skip_mfa']) &&
    $isSelfSetup &&
    ($user['role'] ?? null) !== 'Administrador'
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

/* 🔹 ativar MFA */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
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
        <?= htmlspecialchars($user['username'] ?? 'Utilizador') ?>
    </h2>

    <?php if (
        $isSelfSetup &&
        ($user['role'] ?? null) === 'Administrador'
    ): ?>

        <p><strong>Configuração obrigatória de segurança</strong></p>

    <?php endif; ?>

    <p>1. Faz scan deste QR Code no Google Authenticator:</p>

    <img src="<?= htmlspecialchars($qr) ?>" alt="QR Code">

    <p>2. Insere o código da app:</p>

    <form method="POST">

        <?php if (!isset($_SESSION['mfa_setup_user_id'])): ?>
            <input type="hidden" name="enable_mfa" value="1">
        <?php endif; ?>

        <input
            name="code"
            placeholder="000000"
            required
        >

        <button type="submit">Ativar MFA</button>

    </form>

    <?php if (
        $isSelfSetup &&
        ($user['role'] ?? null) !== 'Administrador' &&
        isset($_SESSION['mfa_setup_user_id'])
    ): ?>

        <form method="POST">
            <button type="submit" name="skip_mfa">
                Ignorar por agora
            </button>
        </form>

    <?php endif; ?>

    <?php if (isset($error)): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

</div>

<?php include "includes/footer.php"; ?>