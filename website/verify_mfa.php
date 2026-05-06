<?php
session_start();

require "includes/db.php";
require "includes/mfa.php";

if (!isset($_SESSION['mfa_user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['mfa_user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $code = $_POST["code"];

    if (mfa_verify_code($user["mfa_secret"], $code)) {

        mfa_mark_authenticated($user);

        // 🔥 atualizar last_login
        $conn->prepare("
            UPDATE users 
            SET last_login = NOW()
            WHERE id = ?
        ")->execute([$user_id]);

        header("Location: dashboard.php");
        exit;

    } else {
        $error = "Código inválido";
    }
}
?>

<?php include "includes/header.php"; ?>

<form method="POST">
    <input name="code" placeholder="Código MFA" required>
    <button type="submit">Verificar</button>
</form>

<?php if (isset($error)) echo $error; ?>

<?php include "includes/footer.php"; ?>