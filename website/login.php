<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require "includes/db.php";
require "includes/auth.php";
require "includes/mfa.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        // 🔍 PRIMEIRO LOGIN
        $isFirstLogin = is_null($user['last_login']);

        // =========================
        // 🔐 ADMIN
        // =========================
        if ($user['role'] === 'admin') {

            // 👉 PRIMEIRO LOGIN → SETUP obrigatório
            if ($isFirstLogin) {
                $_SESSION['mfa_setup_user_id'] = $user['id'];
                header("Location: setup_mfa.php");
                exit;
            }

            // 👉 Sem MFA (estado inválido) → FORÇA setup
            if (!$user['mfa_enabled']) {
                $_SESSION['mfa_setup_user_id'] = $user['id'];
                header("Location: setup_mfa.php");
                exit;
            }

            // 👉 MFA ativo → VERIFY
            $_SESSION['mfa_user_id'] = $user['id'];
            header("Location: verify_mfa.php");
            exit;
        }

        // =========================
        // 👨‍⚕️ OUTROS USERS
        // =========================

        // 👉 PRIMEIRO LOGIN sem MFA → sugerir setup
        if ($isFirstLogin && !$user['mfa_enabled']) {
            $_SESSION['mfa_setup_user_id'] = $user['id'];
            header("Location: setup_mfa.php");
            exit;
        }

        // 👉 MFA ativo → VERIFY
        if ($user['mfa_enabled']) {
            $_SESSION['mfa_user_id'] = $user['id'];
            header("Location: verify_mfa.php");
            exit;
        }

        // =========================
        // ✅ LOGIN NORMAL
        // =========================
        mfa_mark_authenticated($user);

        // 🔥 atualizar last_login
        $conn->prepare("
            UPDATE users 
            SET last_login = NOW() 
            WHERE id = ?
        ")->execute([$user['id']]);

        header("Location: dashboard.php");
        exit;

    } else {
        $error = "Credenciais inválidas";
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="login-container">
    <div class="card login-card">

        <h2>🩺 Meditrust Login</h2>
        <p>Acesso ao sistema clínico</p>

        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input name="username" placeholder="Utilizador" required>
            <input name="password" type="password" placeholder="Password" required>
            <button type="submit">Entrar</button>
        </form>

    </div>
</div>

<?php include "includes/footer.php"; ?>