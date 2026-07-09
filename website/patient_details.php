<?php
require "includes/db.php";
require "includes/auth.php";

requireAuth();

/* permissões */
if (
    $_SESSION["user"]["role"] !== "Administrador" &&
    $_SESSION["user"]["role"] !== "Recepcionista" &&
    $_SESSION["user"]["role"] !== "Doutor"
) {
    die("Acesso negado");
}

$patient_id = $_GET['id'] ?? null;

if (!$patient_id) {
    die("Paciente inválido");
}

/* paciente */
$stmt = $conn->prepare("
    SELECT *
    FROM patients
    WHERE id = ?
");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Paciente não encontrado");
}

/* consultas */
$stmt = $conn->prepare("
    SELECT 
        c.*,
        d.username AS doctor_name,
        u.username AS created_by_name
    FROM consultations c
    LEFT JOIN users d ON c.doctor_id = d.id
    LEFT JOIN users u ON c.created_by = u.id
    WHERE c.patient_id = ?
    ORDER BY c.consultation_date DESC
");

$stmt->execute([$patient_id]);
$consultations = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2><?= htmlspecialchars($patient['name']) ?></h2>
    <p>
        <strong>CC:</strong> <?= htmlspecialchars($patient['citizen_card']) ?> |
        <strong>Utente:</strong> <?= htmlspecialchars($patient['health_number']) ?>
    </p>
    <p>
        <strong>Telefone:</strong> <?= htmlspecialchars($patient['phone']) ?> |
        <strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?>
    </p>
    <p>
        <strong>Data Nasc.:</strong>
        <?= htmlspecialchars($patient['birthdate']) ?>
    </p>

    <hr>

    <h3>Consultas</h3>

    <a href="consultation_create.php?patient_id=<?= $patient['id'] ?>" class="btn">+ Nova Consulta</a>
    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Médico</th>
                <th>Criado por</th>
                <th>Estado</th>
                <th>Diagnóstico</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($consultations) === 0): ?>
            <tr>
                <td colspan="6">
                    Sem consultas registadas.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($consultations as $c): ?>
                <tr>
                    <td><?= date("d/m/Y H:i", strtotime($c['consultation_date'])) ?></td>
                    <td><?= htmlspecialchars($c['doctor_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($c['created_by_name'] ?? '—') ?></td>
                    <td>

                        <?php if ($c['status'] === 'Agendada'): ?>
                            <span style="color:blue;">Agendada</span>

                        <?php elseif ($c['status'] === 'Executada'): ?>
                            <span style="color:green;">Executada</span>

                        <?php else: ?>
                            <span style="color:red;">Cancelada</span>

                        <?php endif; ?>

                    </td>

                    <td>
                        <?= htmlspecialchars(substr($c['diagnosis'], 0, 50)) ?>
                    </td>

                    <td>
                        <a href="consultation_details.php?id=<?= $c['id'] ?>">
                            Ver
                        </a>
                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>

<?php include "includes/footer.php"; ?>