<?php
session_start();

require "includes/db.php";
require "includes/auth.php";
require "includes/mfa.php";
require_once "includes/GoogleAuthenticator.php";

if (!isset($_SESSION['mfa_setup_user_id']) && !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$ga = new PHPGangsta_GoogleAuthenticator();

// 🔹 detectar modo
if (isset($_SESSION['mfa_setup_user_id'])) {
    // 👉 self setup (primeiro login)
    $user_id = $_SESSION['mfa_setup_user_id'];
    $isSelfSetup = true;

} elseif (isset($_GET['id'])) {
    // 👉 admin setup
    if ($_SESSION["user"]["role"] !== "Administrador") {
        die("Acesso negado");
    }

    $user_id = $_GET['id'];
    $isSelfSetup = false;

} else {
    die("Acesso inválido");
}

// 🔹 buscar user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User não existe");
}

// 🔹 gerar secret se necessário
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

// 🔹 QR Code
$qr = $ga->getQRCodeGoogleUrl(
    "Meditrust:" . $user['username'],
    $secret
);

// 🔹 confirmar código
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $code = $_POST["code"];

    if ($ga->verifyCode($secret, $code, 2)) {

        $conn->prepare("
            UPDATE users 
            SET mfa_enabled = 1,
                last_login = NOW()
            WHERE id = ?
        ")->execute([$user_id]);

        // 👉 limpar sessão se for self setup
        if ($isSelfSetup) {
            unset($_SESSION['mfa_setup_user_id']);
            $_SESSION['user'] = $user;
        }

        header("Location: dashboard.php");
        exit;

    } else {
        $error = "Código inválido";
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2>Ativar MFA para <?= htmlspecialchars($user['username']) ?></h2>

    <?php if ($isSelfSetup): ?>
        <p><strong>Configuração obrigatória de segurança</strong></p>
    <?php endif; ?>

    <p>1. Faz scan deste QR Code no Google Authenticator:</p>

    <img src="<?= $qr ?>">

    <p>2. Insere o código da app:</p>

    <form method="POST">
        <input name="code" placeholder="000000" required>
        <button type="submit">Ativar MFA</button>
    </form>

    <?php if (isset($error)) echo "<p>$error</p>"; ?>

</div>

<?php include "includes/footer.php"; ?>