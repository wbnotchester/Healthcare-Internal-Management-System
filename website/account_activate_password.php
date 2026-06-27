<?php
session_start();

require "db.php";
require "audit.php";

if (!isset($_SESSION["activation_user_id"])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION["activation_user_id"];

$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["confirm_password"] ?? "";

    if ($password !== $confirm) {
        $error = "As passwords não coincidem.";
    }

    elseif (strlen($password) < 8) {
        $error = "A password deve ter pelo menos 8 caracteres.";
    }

    elseif (
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[^A-Za-z0-9]/', $password)
    ) {
        $error = "A password não cumpre os requisitos mínimos.";
    }

    elseif (preg_match('/^(.)\1+$/', $password)) {
        $error = "Password demasiado simples.";
    }

    elseif (preg_match('/^(0123456789|12345678|123456789|abcdefgh|password)$/i', $password)) {
        $error = "Escolha uma password mais segura.";
    }

    else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE users
            SET
                password = ?,
                must_change_password = 0,
                activation_token = NULL,
                password_changed_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $hash,
            $userId
        ]);

        auditLog(
            $conn,
            'USER',
            'PASSWORD_CREATED',
            $user["username"],
            $userId,
            'Password criada no primeiro acesso'
        );

        $_SESSION["mfa_setup_user_id"] = $userId;
        unset($_SESSION["activation_user_id"]);

        header("Location: setup_mfa.php");
        exit();
    }
}
?>

<link rel="stylesheet" href="../style.css">

<div class="login-container">
    <div class="card login-card">

        <h2>🔑 Criar Password</h2>

        <p class="mfa-text">
            Bem-vindo,<strong><?= htmlspecialchars($user["username"]) ?></strong>.
            <br>Antes de utilizar o sistema deverá criar uma password segura.
        </p>

        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" id="passwordForm">

            <input type="password" name="password" id="password" placeholder="Nova Password" required>
            <input type="password" name="confirm_password" id="confirm" placeholder="Confirmar Password" required>

            <div class="password-strength">

                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>

                <p id="strengthText">Força da password</p>

            </div>

            <div class="password-rules">
                <p id="length" class="invalid">✖ Pelo menos 8 caracteres</p>
                <p id="upper" class="invalid">✖ Uma letra maiúscula</p>
                <p id="lower" class="invalid">✖ Uma letra minúscula</p>
                <p id="number" class="invalid">✖ Um número</p>
                <p id="special" class="invalid">✖ Um carácter especial</p>
                <p id="match" class="invalid">✖ As passwords coincidem</p>
            </div>
            <button type="submit" id="submitBtn" disabled>Continuar</button>
        </form>

    </div>
</div>

<script>

const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirm");
const submitBtn = document.getElementById("submitBtn");
const fill = document.getElementById("strengthFill");
const text = document.getElementById("strengthText");

const rules = {
    length: document.getElementById("length"),
    upper: document.getElementById("upper"),
    lower: document.getElementById("lower"),
    number: document.getElementById("number"),
    special: document.getElementById("special"),
    match: document.getElementById("match")
};

function setRule(element, valid){

    element.classList.toggle("valid", valid);
    element.classList.toggle("invalid", !valid);

    element.textContent =
        (valid ? "✔ " : "✖ ") +
        element.textContent.substring(2);

}

function validate(){

    const value = password.value;

    const checks = {

        length: value.length >= 8,
        upper: /[A-Z]/.test(value),
        lower: /[a-z]/.test(value),
        number: /\d/.test(value),
        special: /[^A-Za-z0-9]/.test(value),
        match:
            value.length > 0 &&
            value === confirmPassword.value

    };

    Object.keys(checks).forEach(key=>{
        setRule(rules[key], checks[key]);
    });

    let score = 0;

    Object.values(checks).forEach(v=>{
        if(v) score++;
    });

    const percent = (score / 6) * 100;

    fill.style.width = percent + "%";

    if(score <= 2){
        fill.style.background = "#e74c3c";
        text.textContent = "Password fraca";

    }
    else if(score <= 4){
        fill.style.background = "#f39c12";
        text.textContent = "Password média";

    }
    else{
        fill.style.background = "#27ae60";
        text.textContent = "Password forte";

    }

    submitBtn.disabled = score !== 6;

}

password.addEventListener("input", validate);
confirmPassword.addEventListener("input", validate);

</script>