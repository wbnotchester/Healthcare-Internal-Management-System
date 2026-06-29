<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/audit.php";

requireAuth();

/* permissões */
if ($_SESSION["user"]["role"] !== "Administrador" && $_SESSION["user"]["role"] !== "Recepcionista") {
    die("Acesso negado");
}

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Paciente inválido");
}

/* buscar paciente */
$stmt = $conn->prepare("
    SELECT *
    FROM patients
    WHERE id = ?
");
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Paciente não encontrado");
}

$error = "";
$success = "";

$oldPatient = $patient;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $birthdate = $_POST["birthdate"];
    $gender = $_POST["gender"];

    $citizen_card = trim($_POST["citizen_card"]);
    $health_number = trim($_POST["health_number"]);
    $social_security = trim($_POST["social_security"]);

    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);

    if(empty($name) || empty($birthdate) || empty($gender) || empty($citizen_card) || empty($health_number) || empty($phone)){
        $error = "Preencha os campos obrigatórios.";
    } else {
        /* duplicados (excluindo o próprio) */
        $stmt = $conn->prepare("
            SELECT id
            FROM patients
            WHERE (citizen_card = ? OR health_number = ?)
            AND id != ?
        ");

        $stmt->execute([
            $citizen_card,
            $health_number,
            $id
        ]);

        if ($stmt->fetch()) {
            $error = "Já existe outro paciente com esses dados.";
        } else {
            $stmt = $conn->prepare("
                UPDATE patients
                SET
                    name = ?,
                    birthdate = ?,
                    gender = ?,
                    citizen_card = ?,
                    health_number = ?,
                    social_security = ?,
                    phone = ?,
                    email = ?,
                    updated_at = NOW()
                WHERE id = ?
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
                $id
            ]);

            $changes = [];

            if ($oldPatient['name'] !== $name) {
                $changes['name'] = [
                    'old' => $oldPatient['name'],
                    'new' => $name
                ];
            }

            if ($oldPatient['birthdate'] !== $birthdate) {
                $changes['birthdate'] = [
                    'old' => $oldPatient['birthdate'],
                    'new' => $birthdate
                ];
            }

            if ($oldPatient['gender'] !== $gender) {
                $changes['gender'] = [
                    'old' => $oldPatient['gender'],
                    'new' => $gender
                ];
            }

            if ($oldPatient['phone'] !== $phone) {
                $changes['phone'] = [
                    'old' => $oldPatient['phone'],
                    'new' => $phone
                ];
            }

            if ($oldPatient['email'] !== $email) {
                $changes['email'] = [
                    'old' => $oldPatient['email'],
                    'new' => $email
                ];
            }

            $success = "Paciente atualizado com sucesso.";

            if (!empty($changes)) {
                auditLog(
                    $conn,
                    'PATIENT',
                    'PATIENT_UPDATED',
                    $name,
                    $id,
                    json_encode([
                        'description' => 'Dados do paciente atualizados',
                        'old_name' => $oldPatient['name'],
                        'new_name' => $name,
                        'changes' => $changes
                    ])
                );
            }

            $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->execute([$id]);
            $patient = $stmt->fetch();
        }
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2>Editar Paciente</h2>

    <?php if ($error): ?>
        <p style="color:red;"> <?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green;"> <?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" class="form-grid">

        <div class="form-field"><label>Nome *</label><input type="text" name="name" value="<?= htmlspecialchars($patient['name']) ?>" required></div>
        <div class="form-row">
            <div class="form-field"><label>Data de Nascimento *</label><input type="date" name="birthdate" value="<?= $patient['birthdate'] ?>" required></div>
            <div class="form-field">
                <label>Género *</label>
                <select name="gender" required>
                    <option value="Masculino" <?= $patient['gender']=='Masculino'?'selected':'' ?>>Masculino</option>
                    <option value="Feminino" <?= $patient['gender']=='Feminino'?'selected':'' ?>>Feminino</option>
                    <option value="Não Binário" <?= $patient['gender']=='Não Binário'?'selected':'' ?>>Não Binário</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-field"><label>Cartão de Cidadão *</label><input type="text" name="citizen_card" value="<?= htmlspecialchars($patient['citizen_card']) ?>" required></div>
            <div class="form-field"><label>Nº Utente *</label><input type="text" name="health_number" value="<?= htmlspecialchars($patient['health_number']) ?>" required></div>
        </div>

        <div class="form-row">
            <div class="form-field"><label>Segurança Social</label><input type="text" name="social_security" value="<?= htmlspecialchars($patient['social_security']) ?>"></div>
            <div class="form-field"><label>Telefone *</label><input type="text" name="phone" value="<?= htmlspecialchars($patient['phone']) ?>" required></div>
        </div>

        <div class="form-field"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($patient['email']) ?>"></div>
        <div class="form-actions"><button type="submit">Guardar Alterações</button></div>

    </form>

</div>

<?php include "includes/footer.php"; ?>