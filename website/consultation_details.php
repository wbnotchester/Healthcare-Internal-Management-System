<?php
require "includes/db.php";
require "includes/auth.php";

requireAuth();

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Consulta inválida");
}

/*busca consulta*/
$stmt = $conn->prepare("
    SELECT
        c.*,
        p.name AS patient_name,
        p.health_number,
        u.nome AS doctor_name,
        creator.nome AS created_by_name
    FROM consultations c

    INNER JOIN patients p
        ON c.patient_id = p.id

    INNER JOIN users u
        ON c.doctor_id = u.id

    LEFT JOIN users creator
        ON c.created_by = creator.id

    WHERE c.id = ?
");

$stmt->execute([$id]);

$consultation = $stmt->fetch();

if (!$consultation) {
    die("Consulta não encontrada");
}

include "includes/header.php";
?>

<div class="card">
    <h2>Detalhes da Consulta</h2>
    <hr>
    <h3>📅 Informações da Consulta</h3>

    <p><strong>Data:</strong><?= date("d/m/Y H:i", strtotime($consultation["consultation_date"])) ?></p>

    <p><strong>Estado:</strong><?= htmlspecialchars($consultation["status"]) ?></p>

    <p><strong>Médico:</strong><?= htmlspecialchars($consultation["doctor_name"]) ?></p>

    <p><strong>Criada por:</strong><?= htmlspecialchars($consultation["created_by_name"] ?? 'Sistema') ?></p>

    <hr>

    <h3>👤 Paciente</h3>
    <p><strong>Nome:</strong><?= htmlspecialchars($consultation["patient_name"]) ?></p>

    <p><strong>Nº Utente:</strong><?= htmlspecialchars($consultation["health_number"]) ?></p>

    <p> <a href="patient_details.php?id=<?= $consultation['patient_id'] ?>"> Ver ficha do paciente </a>
    </p>

    <hr>

    <h3>📝 Notas</h3>
    <div class="notes-box">
        <?php if (!empty($consultation["notes"])): ?>
            <?= nl2br(htmlspecialchars($consultation["notes"])) ?>
        <?php else: ?>
            <em>Sem notas registadas.</em>
        <?php endif; ?>
    </div>

    <hr>

    <h3>🩺 Diagnóstico</h3>
    <div class="notes-box">
        <?php if (!empty($consultation["diagnosis"])): ?>
            <?= nl2br(htmlspecialchars($consultation["diagnosis"])) ?>
        <?php else: ?>
            <em>Sem diagnóstico registado.</em>
        <?php endif; ?>

    </div>

    <hr>

    <div style="display:flex; gap:10px;">
        <?php if ($_SESSION["user"]["role"] === "Administrador" || $_SESSION["user"]["role"] === "Recepcionista" || $_SESSION["user"]["role"] === "Doutor"): ?>
            <a href="consultation_edit.php?id=<?= $consultation['id'] ?>" class="btn" > ✏️ Editar Consulta </a>
        <?php endif; ?>

        <a href="consultations.php"class="btn"> ⬅️ Voltar </a>

    </div>

</div>

<?php include "includes/footer.php"; ?>