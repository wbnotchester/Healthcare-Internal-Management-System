<?php
require "includes/db.php";
require "includes/auth.php";

requireAuth();

$role = $_SESSION["user"]["role"];
$user_id = $_SESSION["user"]["id"];

if ($role === "Medico") {

    $stmt = $conn->prepare("
        SELECT 
            c.*,
            p.name AS patient_name,
            u.username AS doctor_name
        FROM consultations c
        INNER JOIN patients p ON c.patient_id = p.id
        INNER JOIN users u ON c.doctor_id = u.id
        WHERE c.doctor_id = ?
        ORDER BY c.consultation_date ASC
    ");

    $stmt->execute([$user_id]);

} else {

    $stmt = $conn->prepare("
        SELECT 
            c.*,
            p.name AS patient_name,
            u.username AS doctor_name
        FROM consultations c
        INNER JOIN patients p ON c.patient_id = p.id
        INNER JOIN users u ON c.doctor_id = u.id
        ORDER BY 
            CASE 
                WHEN c.status = 'Agendada' THEN 0 
                ELSE 1 
            END,
            c.consultation_date ASC
    ");

    $stmt->execute();
}

$consultations = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2>Consultas</h2>

    <table class="table">

        <thead>
            <tr>
                <th>Data / Hora</th>
                <th>Paciente</th>
                <th>Médico</th>
                <th>Estado</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>

        <?php if (count($consultations) === 0): ?>

            <tr>
                <td colspan="5">
                    Sem consultas.
                </td>
            </tr>

        <?php else: ?>

            <?php foreach ($consultations as $c): ?>

                <tr>

                    <td><?= date("d/m/Y H:i", strtotime($c['consultation_date'])) ?></td>

                    <td><?= htmlspecialchars($c['patient_name']) ?></td>

                    <td><?= htmlspecialchars($c['doctor_name']) ?></td>

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