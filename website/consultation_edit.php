<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/audit.php";

requireAuth();

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Consulta inválida");
}

/*busca consulta */
$stmt = $conn->prepare("
    SELECT *
    FROM consultations
    WHERE id = ?
");

$stmt->execute([$id]);

$consultation = $stmt->fetch();

if (!$consultation) {
    die("Consulta não encontrada");
}

/*busca paciente*/
$patients = $conn->query("
    SELECT id, name
    FROM patients
    ORDER BY name ASC
")->fetchAll();

/*busca médico*/
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
    $consultation_date = $_POST["consultation_date"];
    $status = $_POST["status"];
    $notes = trim($_POST["notes"] ?? '');
    $diagnosis = trim($_POST["diagnosis"] ?? '');
    if (
        empty($patient_id) ||
        empty($doctor_id) ||
        empty($consultation_date) ||
        empty($status)
    ) {

        $error = "Preenche os campos obrigatórios.";

    } else {

        $changes = []; //Apenas regista aqueles que mudaram

        if ($consultation['patient_id'] != $patient_id) {
            $changes['patient_id'] = [
                'old' => $consultation['patient_id'],
                'new' => $patient_id
            ];
        }

        if ($consultation['doctor_id'] != $doctor_id) {
            $changes['doctor_id'] = [
                'old' => $consultation['doctor_id'],
                'new' => $doctor_id
            ];
        }

        if ($consultation['consultation_date'] != $consultation_date) {
            $changes['consultation_date'] = [
                'old' => $consultation['consultation_date'],
                'new' => $consultation_date
            ];
        }

        if ($consultation['status'] != $status) {
            $changes['status'] = [
                'old' => $consultation['status'],
                'new' => $status
            ];
        }

        $stmt = $conn->prepare("
            UPDATE consultations
            SET
                patient_id = ?,
                doctor_id = ?,
                consultation_date = ?,
                status = ?,
                notes = ?,
                diagnosis = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $patient_id,
            $doctor_id,
            $consultation_date,
            $status,
            $notes,
            $diagnosis,
            $id
        ]);

        auditLog(
            $conn,
            "CONSULTATION_UPDATED",
            "consultation",
            $id,
            json_encode($changes)
        );

        $success = "Consulta atualizada com sucesso.";

        $stmt = $conn->prepare("
            SELECT *
            FROM consultations
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $consultation = $stmt->fetch();
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2>Editar Consulta</h2>

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

        <label>Paciente *</label>

        <select name="patient_id" required>

            <option value="">Selecionar paciente</option>

            <?php foreach ($patients as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $consultation['patient_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>

        </select>

        <label>Médico *</label>

        <select name="doctor_id" required>

            <option value="">Selecionar médico</option>
            <?php foreach ($doctors as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $consultation['doctor_id'] == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['username']) ?></option>
            <?php endforeach; ?>

        </select>

        <label>Data e Hora *</label>

        <input type="datetime-local" name="consultation_date" value="<?= date('Y-m-d\TH:i', strtotime($consultation['consultation_date'])) ?>" required>

        <label>Estado *</label>

        <select name="status" required>
            <option value="Agendada" <?= $consultation['status'] === 'Agendada' ? 'selected' : '' ?> > Agendada </option>
            <option value="Executada" <?= $consultation['status'] === 'Executada' ? 'selected' : '' ?> > Executada </option>
            <option value="Cancelada" <?= $consultation['status'] === 'Cancelada' ? 'selected' : '' ?> > Cancelada </option>
        </select>

        <label>Notas</label>

        <textarea name="notes" rows="4" placeholder="Motivo da consulta, observações..."><?= htmlspecialchars($consultation['notes']) ?></textarea>

        <label>Diagnóstico</label>

        <textarea name="diagnosis" rows="5" placeholder="Diagnóstico clínico..."><?= htmlspecialchars($consultation['diagnosis']) ?></textarea>

        <button type="submit">Guardar Alterações</button>

    </form>

</div>

<?php include "includes/footer.php"; ?>