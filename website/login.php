<?php
require "includes/db.php";
require "includes/auth.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user"] = $user;

        logEvent("Login sucesso: " . $username);

        header("Location: dashboard.php");
        exit();
    } else {
        logEvent("Login falhado: " . $username);
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
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <input name="username" placeholder="Utilizador" required>
            <input name="password" type="password" placeholder="Password" required>
            <button type="submit">Entrar</button>
        </form>

    </div>
</div>

<?php include "includes/footer.php"; ?>