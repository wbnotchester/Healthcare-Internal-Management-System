<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/audit.php";

requireAuth();

if (
    $_SESSION["user"]["role"] !== "Administrador" &&
    $_SESSION["user"]["role"] !== "Recepcionista"
) {
    die("Acesso negado");
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $birthdate = $_POST["birthdate"];
    $gender = $_POST["gender"];

    $citizen_card = trim($_POST["citizen_card"]);
    $health_number = trim($_POST["health_number"]);
    $social_security = trim($_POST["social_security"]);

    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);

    $address = trim($_POST["address"]);

    /* validações */
    if (
        empty($name) ||
        empty($birthdate) ||
        empty($gender) ||
        empty($citizen_card) ||
        empty($health_number) ||
        empty($phone)
    ) {
        $error = "Preencha todos os campos obrigatórios.";

    } else {

        /* verificar duplicados */
        $stmt = $conn->prepare("
            SELECT id
            FROM patients
            WHERE citizen_card = ?
            OR health_number = ?
        ");

        $stmt->execute([
            $citizen_card,
            $health_number
        ]);

        if ($stmt->fetch()) {
            auditLog(
                $conn,
                "PATIENT_CREATION_FAILED",
                "patient",
                null,
                json_encode([
                    "citizen_card" => $citizen_card,
                    "health_number" => $health_number
                ]),
                "WARNING"
            );
            $error = "Já existe um paciente com esses dados.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO patients (
                    name,
                    birthdate,
                    gender,
                    citizen_card,
                    health_number,
                    social_security,
                    phone,
                    email,
                    address
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $birthdate,
                $gender,
                $citizen_card,
                $health_number,
                $social_security,
                $phone,
                $email,
                $address
            ]);

            $patientId = $conn->lastInsertId();
            auditLog(
                $conn,
                "PATIENT_CREATED",
                "patient",
                $patientId,
                json_encode([
                    "name" => $name,
                    "health_number" => $health_number
                ])
            );

            $success = "Paciente registado com sucesso.";
        }
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2>Novo Paciente</h2>

    <?php if ($error): ?>
        <p style="color:red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green;">
            <?= htmlspecialchars($success) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Nome Completo *</label>
        <input type="text" name="name" required>

        <label>Data de Nascimento *</label>
        <input type="date" name="birthdate" required>

        <label>Género *</label>

        <select name="gender" required>
            <option value="Masculino"> Masculino </option>
            <option value="Feminino"> Feminino </option>
            <option value="Não Binário"> Não Binário </option>
        </select>

        <label>Cartão de Cidadão *</label>
        <input type="text" name="citizen_card" required>

        <label>Número de Utente *</label>
        <input type="text" name="health_number" required>

        <label>Segurança Social</label>
        <input type="text" name="social_security">

        <label>Telefone *</label>
        <input type="text" name="phone" required>

        <label>Email</label>
        <input type="email" name="email">

        <label>Morada</label>

        <textarea name="address" rows="4"></textarea>

        <button type="submit">
            Registar Paciente
        </button>

    </form>

</div>

<?php include "includes/footer.php"; ?>