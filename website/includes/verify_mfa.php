<?php
session_start();

require "db.php";
require "mfa.php";
require "audit.php";

if (!isset($_SESSION['mfa_user_id'])) {
    header("Location: ../login.php");
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
        auditLog(
            $conn,
            'MFA',
            'MFA_VERIFICATION_SUCCESS',
            $user['username'],
            $user_id,
            'MFA completo'
        );

        $conn->prepare("
            UPDATE users 
            SET last_login = NOW()
            WHERE id = ?
        ")->execute([$user_id]);

        header("Location: ../dashboard.php");
        exit;

    } else {
        auditLog(
            $conn,
            'MFA',
            'MFA_VERIFICATION_FAILED',
            $user['username'],
            $user_id,
            'Código MFA inválido',
            
        );
        $error = "Código inválido";
    }
}
?>
    <link rel="stylesheet" href="../style.css">
    <div class="login-container">
    <div class="card login-card">
        <h2>🔐 Verificação em Dois Fatores</h2>
        <p class="mfa-text">Introduza o código de 6 dígitos gerado pela sua aplicação autenticadora.</p>
        <form method="POST" id="mfaForm">
        <div class="mfa-inputs">
            <input type="text" maxlength="1" inputmode="numeric" autofocus>
            <input type="text" maxlength="1" inputmode="numeric">
            <input type="text" maxlength="1" inputmode="numeric">
            <input type="text" maxlength="1" inputmode="numeric">
            <input type="text" maxlength="1" inputmode="numeric">
            <input type="text" maxlength="1" inputmode="numeric">
        </div>
        <input type="hidden" name="code" id="code">
        <button type="submit">Verificar Código</button>
    </form>

        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

    </div>
</div>

<script>
const inputs = document.querySelectorAll(".mfa-inputs input");
const hidden = document.getElementById("code");
const form = document.getElementById("mfaForm");

let submitted = false;

function updateCode() {
    hidden.value = [...inputs].map(input => input.value).join("");

    if (hidden.value.length === 6 && !submitted) {
        submitted = true;
        form.requestSubmit();
    }
}

inputs.forEach((input, index) => {

    input.addEventListener("input", () => {

        input.value = input.value.replace(/\D/g, "").slice(0, 1);

        if (input.value !== "") {
            input.classList.add("filled");

            if (index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        } else {
            input.classList.remove("filled");
        }

        updateCode();
    });

    input.addEventListener("keydown", (e) => {

        if (e.key === "Backspace") {

            if (input.value === "" && index > 0) {
                inputs[index - 1].value = "";
                inputs[index - 1].classList.remove("filled");
                inputs[index - 1].focus();
                updateCode();
            }

        }

    });

});

document.addEventListener("paste", (e) => {

    const text = e.clipboardData
        .getData("text")
        .replace(/\D/g, "")
        .slice(0, 6);

    if (text.length === 6) {

        inputs.forEach((input, i) => {
            input.value = text[i];
            input.classList.add("filled");
        });

        updateCode();
    }

});

form.addEventListener("submit", (e) => {

    hidden.value = [...inputs].map(input => input.value).join("");

    if (hidden.value.length !== 6) {
        e.preventDefault();
        alert("Introduza os 6 dígitos do código MFA.");
        submitted = false;
    }

});
</script>