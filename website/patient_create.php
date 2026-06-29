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
                'PATIENT',
                'PATIENT_CREATION_FAILED',
                $name,
                null,
                json_encode([
                    'description' => 'Tentativa de criação de paciente duplicado',
                    'citizen_card' => $citizen_card,
                    'health_number' => $health_number
                ]),
                'WARNING'
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
                'PATIENT',
                'PATIENT_CREATED',
                $name,
                $patientId,
                json_encode([
                    'description' => 'Paciente registado com sucesso',
                    'health_number' => $health_number
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

    <form method="POST" class="form-grid">

        <div class="form-field"><label>Nome Completo *</label><input type="text" name="name" required></div>

        <div class="form-row">
            <div class="form-field"><label>Data de Nascimento *</label><input type="date" name="birthdate" required></div>
            <div class="form-field">
                <label>Género *</label>
                <select name="gender" required>
                    <option value="Masculino">Masculino</option>
                    <option value="Feminino">Feminino</option>
                    <option value="Não Binário">Não Binário</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-field"><label>Cartão de Cidadão *</label><input type="text" name="citizen_card" required></div>
            <div class="form-field"><label>Número de Utente *</label><input type="text" name="health_number" required></div>
        </div>

        <div class="form-row">
            <div class="form-field"><label>Segurança Social</label><input type="text" name="social_security"></div>
            <div class="form-field"><label>Telefone *</label><input type="text" name="phone" required></div>
        </div>

        <div class="form-field"><label>Email</label><input type="email" name="email"></div>
        <div class="form-field"><label>Morada</label><textarea name="address"></textarea></div>
        <div class="form-actions"><button type="submit">Registar Paciente</button></div>

    </form>
</div>

<?php include "includes/footer.php"; ?>