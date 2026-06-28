<?php
require "includes/auth.php";
require "includes/db.php";

requireAuth();

$user = $_SESSION["user"];
$role = $user["role"];

include "includes/header.php";

/*próximas consultas (próxima hora)*/
if ($role === "Medico") {

    $stmt = $conn->prepare("
        SELECT c.*, p.name AS patient_name
        FROM consultations c
        INNER JOIN patients p ON c.patient_id = p.id
        WHERE c.doctor_id = ?
        AND c.consultation_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
        ORDER BY c.consultation_date ASC
    ");

    $stmt->execute([$user["id"]]);

} else {

    $stmt = $conn->prepare("
        SELECT c.*, p.name AS patient_name, u.username AS doctor_name
        FROM consultations c
        INNER JOIN patients p ON c.patient_id = p.id
        INNER JOIN users u ON c.doctor_id = u.id
        WHERE c.consultation_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
        ORDER BY c.consultation_date ASC
    ");

    $stmt->execute();
}

$consultations = $stmt->fetchAll();
?>

<div class="card">

    <h2>Dashboard</h2>
    <p>Bem-vindo, <strong><?= htmlspecialchars($user["username"]) ?></strong>!</p>
    <p><strong><?= htmlspecialchars($role) ?></strong></p>
    <hr>

    <h3>⚡ Acesso rápido</h3>

    <div class="dashboard-grid">

        <a href="patients.php" class="dashboard-card">
            <span class="icon">👤</span>
            <h4>Pacientes</h4>
        </a>

        <?php if ($role === "Recepcionista" || $role === "Administrador"): ?>
            <a href="consultations.php" class="dashboard-card">
                    <span class="icon">📅</span>
                    <h4>Consultas</h4>
            </a>
        <?php endif; ?>

        <?php if ($role === "Doutor"): ?>
            <a href="schedule.php" class="dashboard-card"> <!-- Apenas as consultas dos próprios médicos -->
                    <span class="icon">📅</span>
                    <h4>Consultas</h4>
            </a>
        <?php endif; ?>

        <?php if ($role === "Administrador"): ?>

            <a href="users.php" class="dashboard-card">
                <span class="icon">👨‍💼</span>
                <h4>Utilizadores</h4>
            </a>

            <a href="backup.php" class="dashboard-card">
                <span class="icon">💾</span>
                <h4>Backups</h4>
            </a>

            <a href="logs.php" class="dashboard-card">
                <span class="icon">📜</span>
                <h4>Logs de Auditoria</h4>
            </a>

        <?php endif; ?>

        <a href="account.php" class="dashboard-card">
            <span class="icon">⚙️</span>
            <h4>Minha Conta</h4>
        </a>

    </div>


<!--
    <ul>
        <li><a href="patients.php">👤 Pacientes</a></li>
        <li><a href="patient_create.php">➕ Novo Paciente</a></li>
        <li><a href="consultations.php">📅 Consultas</a></li>
        <li><a href="consultation_create.php">🩺 Nova Consulta</a></li>

        <?php if ($role === "Administrador"): ?> 
            <li><a href="users.php">⚙️ Gestão de Utilizadores</a></li>
            <li><a href="backup.php">📥 Gerar Backup</a></li>
            <li><a href="backup_restore.php">📤 Restaurar Backup</a></li>
            <li><a href="logs.php">📃 Logs de Auditoria</a></li>
        <?php endif; ?>

        <li><a href="account.php">🪪 Minha Conta</a></li>
        <li><a href="logout.php">🚪 Logout</a></li>
    </ul> -->

    <hr> 

    <!-- 📌 CONSULTAS PRÓXIMA HORA -->
    <h3>📌 Consultas na próxima hora</h3>

    <table class="table">

        <thead>
            <tr>
                <th>Data</th>
                <th>Paciente</th>
                <?php if ($role !== "Medico"): ?><th>Médico</th><?php endif; ?>
                <th>Estado</th>
            </tr>
        </thead>

        <tbody>

        <?php if (count($consultations) === 0): ?>

            <tr>
                <td colspan="4">
                    Nenhuma consulta na próxima hora.
                </td>
            </tr>

        <?php else: ?>

            <?php foreach ($consultations as $c): ?>

                <tr>

                    <td><?= date("d/m H:i", strtotime($c["consultation_date"])) ?></td>

                    <td><?= htmlspecialchars($c["patient_name"]) ?></td>

                    <?php if ($role !== "Medico"): ?>
                        <td><?= htmlspecialchars($c["doctor_name"]) ?></td>
                    <?php endif; ?>

                    <td><?= htmlspecialchars($c["status"]) ?></td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>

<?php include "includes/footer.php"; ?>