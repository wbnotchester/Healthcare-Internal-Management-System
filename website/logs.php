<?php
require "includes/db.php";
require "includes/auth.php";

requireAuth();

if ($_SESSION['user']['role'] !== 'Administrador') {
    die("Acesso negado");
}

$severity = $_GET['severity'] ?? '';
$logType = $_GET['log_type'] ?? '';
$username = trim($_GET['username'] ?? '');
$action = trim($_GET['action'] ?? '');
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$sql = "
    SELECT
        audit_logs.*,
        users.email,
        users.phone
    FROM audit_logs
    LEFT JOIN users
        ON users.id = audit_logs.user_id
    WHERE 1=1
";

$params = [];

if ($severity !== '') {
    $sql .= " AND severity = ?";
    $params[] = $severity;
}

if ($logType !== '') {
    $sql .= " AND log_type = ?";
    $params[] = $logType;
}

if ($username !== '') {
    $sql .= " AND audit_logs.username LIKE ?";
    $params[] = "%{$username}%";
}

if ($action !== '') {
    $sql .= " AND audit_logs.action LIKE ?";
    $params[] = "%{$action}%";
}

if ($from !== '') {
    $sql .= " AND DATE(audit_logs.happened_at) >= ?";
    $params[] = $from;
}

if ($to !== '') {
    $sql .= " AND DATE(audit_logs.happened_at) <= ?";
    $params[] = $to;
}

$sql .= " ORDER BY audit_logs.happened_at DESC LIMIT 500";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Logs de Auditoria";

function timeAgo($datetime){
    $time = strtotime($datetime);
    $diff = time() - $time - 3600; // Ajuste de fuso horário para -1 hora
    if ($diff < 60) {
        return "Agora mesmo";
    }
    if ($diff < 3600) {
        return floor($diff / 60) . " min atrás";
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . " h atrás";
    }
    if ($diff < 2592000) {
        return floor($diff / 86400) . " dias atrás";
    }
    return date("d/m/Y H:i", $time);
}

include "includes/header.php";
?>

<div class="container">
<div class="card">

    <div class="page-header"><h2>Logs de Auditoria</h2>
    <button type="button" class="filter-toggle" onclick="toggleFilters()">🔍 Filtros</button>
    
        <div id="filters-panel" class="hidden">

    <form method="GET" class="filters-form">

        <div class="filter-row full">
            <div class="filter-field">
                <label>Utilizador</label>
                <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" placeholder="Nome do utilizador">
            </div>
        </div>

        <div class="filter-row three-cols">

            <div class="filter-field">
                <label>Severidade</label>
                <select name="severity">
                    <option value="">Todas</option>
                    <option value="INFO" <?= $severity === 'INFO' ? 'selected' : '' ?>>INFO</option>
                    <option value="WARNING" <?= $severity === 'WARNING' ? 'selected' : '' ?>>WARNING</option>
                    <option value="CRITICAL" <?= $severity === 'CRITICAL' ? 'selected' : '' ?>>CRITICAL</option>
                </select>
            </div>

            <div class="filter-field">
                <label>Tipo</label>
                <select name="log_type">
                    <option value="">Todos</option>

                    <?php $types = ['LOGIN','MFA','PATIENT','APPOINTMENT','EXAM','USER','SYSTEM'];
                    foreach ($types as $type): ?>
                        <option value="<?= $type ?>" <?= $logType === $type ? 'selected' : '' ?>><?= $type ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field">
                <label>Ação</label>
                <input type="text" name="action" value="<?= htmlspecialchars($action) ?>" placeholder="LOGIN, MFA...">
            </div>

        </div>

        <div class="filter-row two-cols">

            <div class="filter-field">
                <label>Data inicial</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
            </div>

            <div class="filter-field">
                <label>Data final</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
            </div>

        </div>

        <button type="submit">Filtrar</button>

    </form>
    </div>
</div>

    <div class="table-responsive">
    <table>
        <thead>
        <tr>
            <th>Data</th>
            <th>Severidade</th>
            <th>Tipo</th>
            <th>Utilizador</th>
            <th>Ação</th>
            <th>Entidade</th>
            <th>Detalhes</th>
            <th>Mais</th>
        </tr>
        </thead>

        <tbody>

        <?php foreach ($logs as $log): ?>
        <tr data-id="<?= $log['id'] ?>" data-log='<?= htmlspecialchars(json_encode($log), ENT_QUOTES, 'UTF-8') ?>'>

        <td title="<?= htmlspecialchars($log['happened_at']) ?>"><?= timeAgo($log['happened_at']) ?></td>

                <td>
                    <?php if ($log['severity'] === 'INFO'): ?>
                        <span class="badge-success">INFO</span>
                    <?php elseif ($log['severity'] === 'WARNING'): ?>
                        <span class="badge-warning">WARNING</span>
                    <?php else: ?>
                        <span class="badge-danger">CRITICAL</span>
                    <?php endif; ?>

                </td>

                <td><?= htmlspecialchars($log['log_type']) ?></td>

                <td><?= htmlspecialchars($log['username'] ?? 'Sistema') ?></td>

                <td><?= htmlspecialchars($log['action']) ?></td>

                <td>
                    <?php $entity = $log['entity_name'];
                    if ($log['entity_id']) {
                        $entity .= " (#{$log['entity_id']})";
                    }
                    echo htmlspecialchars($entity ?: '-'); ?>
                </td>

                <td><?= htmlspecialchars( mb_strimwidth($log['details'] ?? '-',0,40,'...'))?></td>
                <td><button type="button" class="view-log-btn" onclick="openLog(<?= $log['id'] ?>)">Ver</button></td>
</tr>
<?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div id="logModal" class="modal hidden">
    <div class="modal-content">
        <span class="close-modal" onclick="closeLog()">&times;</span>
        <div id="logContent"></div>
    </div>
    </div>
</div>

<script>

function openLog(id){
    const row = document.querySelector(
        `tr[data-id="${id}"]`
    );

    const log = JSON.parse(row.dataset.log);

    let html = `
        <h3>Log #${log.id}</h3>
        <p><strong>Severidade:</strong> ${log.severity}</p>
        <p><strong>Tipo:</strong> ${log.log_type}</p>
        <p><strong>Data:</strong> ${log.happened_at}</p>
        <p><strong>Utilizador:</strong> ${log.username ?? 'Sistema'}</p>
        <p><strong>Ação:</strong> ${log.action}</p>
        <p><strong>Entidade:</strong>${log.entity_name ?? '-'}</p>
        <p><strong>Detalhes:</strong></p>
        <p>${log.details ?? '-'}</p>
    `;

    if (log.email) {
        html += `<a class="btn-primary" href="mailto:${log.email}">Enviar Email</a>`;
    }

    if (log.phone) {
        html += `<a class="btn-warning" href="tel:${log.phone}">Telefonar</a>`;
    }

    document.getElementById('logContent').innerHTML = html;

    document
        .getElementById('logModal')
        .classList.remove('hidden');
}

function closeLog(){
    document
        .getElementById('logModal')
        .classList.add('hidden');
}

function toggleFilters(){
    document
        .getElementById('filters-panel')
        .classList.toggle('hidden');
}

</script>

<?php include "includes/footer.php"; ?>