<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/audit.php";

requireAuth();

$patient_id = $_GET['patient_id'] ?? null;

/* busca pacientes */
$patients = $conn->query("
    SELECT id, name
    FROM patients
    ORDER BY name ASC
")->fetchAll();

/* busca médicos */
$doctors = $conn->prepare("
    SELECT id, username
    FROM users
    WHERE role = 'Doutor'
    ORDER BY username ASC
");
$doctors->execute();
$doctors = $doctors->fetchAll();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $patient_id = $_POST["patient_id"];
    $doctor_id = $_POST["doctor_id"];
    $date = $_POST["consultation_date"];
    $notes = trim($_POST["notes"] ?? '');

    if (!$patient_id || !$doctor_id || !$date) {
        $error = "Preenche todos os campos obrigatórios.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO consultations (
                patient_id,
                doctor_id,
                created_by,
                notes,
                consultation_date,
                status
            )
            VALUES (?, ?, ?, ?, ?, 'Agendada')
        ");

        $stmt->execute([
            $patient_id,
            $doctor_id,
            $_SESSION['user']['id'],
            $notes,
            $date
        ]);

        $consultationId = $conn->lastInsertId();

        $patientStmt = $conn->prepare("
            SELECT name
            FROM patients
            WHERE id = ?
        ");
        $patientStmt->execute([$patient_id]);
        $patientName = $patientStmt->fetchColumn();

        $doctorStmt = $conn->prepare("
            SELECT username
            FROM users
            WHERE id = ?
        ");
        $doctorStmt->execute([$doctor_id]);
        $doctorName = $doctorStmt->fetchColumn();

        auditLog(
            $conn,
            "CONSULTATION_CREATED",
            "consultation",
            $consultationId,
            json_encode([
                "patient" => $patientName,
                "doctor" => $doctorName,
                "date" => $date
            ])
        );

        header("Location: patient_details.php?id=" . $patient_id);
        exit();
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2>Nova Consulta</h2>
    <?php if ($error): ?>
        <p style="color:red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Paciente *</label>
        <select name="patient_id" required>
            <option value="">Selecionar paciente</option>
            <?php foreach ($patients as $p): ?>
                <option
                    value="<?= $p['id'] ?>"
                    <?= ($patient_id == $p['id']) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Médico *</label>
        <select name="doctor_id" required>
            <option value="">Selecionar médico</option>
            <?php foreach ($doctors as $d): ?>
                <option value="<?= $d['id'] ?>">
                    <?= htmlspecialchars($d['username']) ?>
                </option>
            <?php endforeach; ?>

        </select>
        <label>Data e Hora *</label>
        <input type="datetime-local" name="consultation_date" required>
        <label>Notas</label>
        <textarea name="notes" rows="4" placeholder="Motivo da consulta, observações iniciais..."></textarea>
        <button type="submit">Criar Consulta</button>

    </form>

</div>

<?php include "includes/footer.php"; ?>