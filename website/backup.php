<?php
require "includes/db.php";
require "includes/auth.php";

requireRole(["Administrador"]);

$stmt = $conn->query("
SELECT
    b.*,
    u.nome AS created_by_name,
    r.nome AS restored_by_name
FROM backups b
JOIN users u
    ON u.id = b.created_by
LEFT JOIN users r
    ON r.id = b.last_restore_by
ORDER BY b.created_at DESC
");

$backups = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Estatísticas */
$totalBackups = count($backups);

$totalStorage = 0;

foreach ($backups as $backup) {
    $totalStorage += $backup["file_size"];
}

$lastBackup = $backups[0]["created_at"] ?? null;

include "includes/header.php";
?>

<div class="container">
    <div class="card">

        <div class="page-header">
            <h2>Gestão de Backups</h2>
            <button type="button" class="btn-primary" id="openCreateBackup"> + Criar Backup</button>
        </div>

        <div class="dashboard-grid">

            <div class="dashboard-card">
                <h4><?= $totalBackups ?></h4><p>Total de Backups</p>
            </div>

            <div class="dashboard-card">
                <h4><?= $lastBackup ? date("d/m/Y H:i", strtotime($lastBackup)) : "-" ?></h4>
                <p>Último Backup</p>
            </div>

            <div class="dashboard-card">
                <h4><?= round($totalStorage / 1024 / 1024, 2) ?> MB</h4><p>Armazenamento Total</p>
            </div>

        </div>

        <div class="dashboard-grid">
            <?php if (empty($backups)): ?>
                <div class="card"><p>No backups available.</p></div>
            <?php endif; ?>

            <?php foreach ($backups as $backup): ?>
                <div class="dashboard-card backup-card" data-id="<?= $backup["id"] ?>" data-file="<?= htmlspecialchars($backup["file_name"]) ?>">
                    <h4>📦 Backup</h4>
                    <p><strong><?= date("d/m/Y", strtotime($backup["created_at"])) ?></strong><br><?= date("H:i", strtotime($backup["created_at"])) ?></p>

                    <hr>
                    <p><?= number_format($backup["total_records"]) ?> records</p>
                    <p><?= $backup["total_patients"] ?> Pacientes</p>
                    <p><?= $backup["total_consultations"] ?> Consultas</p>

                    <hr>
                    <p><?= round($backup["file_size"]/1024,1) ?> KB</p>
                    <p>Criado por <strong><?= htmlspecialchars($backup["created_by_name"]) ?></strong></p>

                    <?php if($backup["status"]=="AVAILABLE"): ?>
                        <span class="badge-success">Disponível</span>

                    <?php elseif($backup["status"]=="CORRUPTED"): ?>
                        <span class="badge-danger">Corrumpido</span>
                    <?php else: ?>
                        <span class="badge-warning">Excluído</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- CREATE MODAL -->
<div class="modal hidden" id="createModal">

    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Create Backup</h2>

        <form action="backup_create.php" method="POST">
            <label>Password Encriptada</label>
            <input type="password" name="password" minlength="6" required>           
            <p>Esta password será necessária para restaurar o backup.</p>

            <div class="modal-actions">
                <button type="button" id="cancelCreate">Cancelar</button>
                <button>Criar Backup</button>
            </div>

        </form>
    </div>
</div>

<!-- RESTORE MODAL -->
<div class="modal hidden" id="restoreModal">

    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Restaurar Backup</h2>

        <form action="backup_restore.php" method="POST">
            <input type="hidden" id="restoreBackupId" name="backup_id">

            <p id="selectedBackup"></p>
            <label>Password</label>
            <input type="password" name="password" required>
            <p>Registos existentes não serão removidos. Apenas os registos em falta serão restaurados.</p>

            <div class="modal-actions">
                <button type="button" id="cancelRestore">Cancelar</button>
                <button>Restaurar Backup</button>

            </div>
        </form>
    </div>
</div>

<script>

const createModal=document.getElementById("createModal");
const restoreModal=document.getElementById("restoreModal");

document.getElementById("openCreateBackup").onclick=()=>{
    createModal.classList.remove("hidden");
};

document.querySelectorAll(".backup-card").forEach(card=>{
    card.onclick=()=>{
        document.getElementById("restoreBackupId").value=card.dataset.id;
        document.getElementById("selectedBackup").textContent=card.dataset.file;
        restoreModal.classList.remove("hidden");
    };

});

document.querySelectorAll(".close-modal").forEach(btn=>{
    btn.onclick=()=>{
        createModal.classList.add("hidden");
        restoreModal.classList.add("hidden");
    };
});

document.getElementById("cancelCreate").onclick=()=>{
    createModal.classList.add("hidden");
};

document.getElementById("cancelRestore").onclick=()=>{
    restoreModal.classList.add("hidden");
};

window.onclick=function(e){
    if(e.target===createModal)
        createModal.classList.add("hidden");

    if(e.target===restoreModal)
        restoreModal.classList.add("hidden");
};
</script>
<?php include "includes/footer.php"; ?>