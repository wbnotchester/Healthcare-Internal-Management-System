<?php
/** ==========================================
 * FICHEIRO: login.php
 * ==========================================
 *
 * Descrição:
 * Página de login responsável pelo acesso ao sistema.
 *
 * Funcionalidades:
 * - Logar
 *
 * Acesso:
 * Todos
 *
 * Dependências:
 * - db.php
 * - auth.php
 * - header.php
 *
 * Autor(github): @wbnotchester
 * ========================================== */

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require "includes/db.php";
require "includes/auth.php";
require "includes/mfa.php";
require "includes/audit.php";

$userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

if ($userCount == 0) {
    header("Location: includes/create_user.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        $isFirstLogin = is_null($user['last_login']);

        if ($isFirstLogin) {
            $_SESSION['mfa_setup_user_id'] = $user['id'];
            auditLog(
                $conn,
                'LOGIN',
                'FIRST_LOGIN',
                null,
                null,
                json_encode([
                    'description' => 'Primeiro acesso ao sistema'
                ]),
                'INFO',
                $user['id'],
                $user['username']
            );
            header("Location: includes/setup_mfa.php");
            exit;
        }

        //MFA ACTIVE → VERIFY
        if ((int)$user['mfa_enabled'] === 1) {
            auditLog(
                $conn,
                'MFA',
                'MFA_CHALLENGE_STARTED',
                null,
                null,
                json_encode([
                    'description' => 'Login com MFA ativo'
                ]),
                'INFO',
                $user['id'],
                $user['username']
            );
            $_SESSION['mfa_user_id'] = $user['id'];
            header("Location: includes/verify_mfa.php");
            exit;
        }

        mfa_mark_authenticated($user);

        $conn->prepare("
            UPDATE users 
            SET last_login = NOW() 
            WHERE id = ?
        ")->execute([$user['id']]);

        auditLog(
            $conn,
            'LOGIN',
            'LOGIN_SUCCESS',
            null,
            null,
            json_encode([
                'description' => 'Login efetuado com sucesso'
            ]),
            'INFO',
            $user['id'],
            $user['username']
        );
        header("Location: dashboard.php");
        exit;

    } else {
        $error = "Credenciais inválidas";
        auditLog(
            $conn,
            'LOGIN',
            'LOGIN_FAILED',
            null,
            null,
            json_encode([
                'description' => 'Credenciais inválidas'
            ]),
            'WARNING',
            null,
            $username
        );
    }
}
?>
    <link rel="stylesheet" href="./style.css">

<div class="login-container">
    <div class="card login-card">

        <h2>🩺 HIMS Login</h2>
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