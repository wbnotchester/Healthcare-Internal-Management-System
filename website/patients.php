<?php
require "includes/db.php";
require "includes/auth.php";

requireAuth();

/* permissões */
if ($_SESSION["user"]["role"] !== "Administrador" &&$_SESSION["user"]["role"] !== "Recepcionista" &&$_SESSION["user"]["role"] !== "Doutor") {
    die("Acesso negado");
}

/* pesquisa */
$search = trim($_GET['search'] ?? '');
$gender = $_GET['gender'] ?? '';
$birthFrom = $_GET['birth_from'] ?? '';
$birthTo = $_GET['birth_to'] ?? '';
$createdFrom = $_GET['created_from'] ?? '';
$createdTo = $_GET['created_to'] ?? '';

$sql = " SELECT * FROM patients WHERE 1=1 ";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            name LIKE ?
            OR citizen_card LIKE ?
            OR health_number LIKE ?
            OR phone LIKE ?
            OR email LIKE ?
        )
    ";

    $term = "%{$search}%";

    array_push(
        $params,
        $term,
        $term,
        $term,
        $term,
        $term
    );
}

if ($gender !== '') {
    $sql .= " AND gender = ?";
    $params[] = $gender;
}

if ($birthFrom !== '') {
    $sql .= " AND birthdate >= ?";
    $params[] = $birthFrom;
}

if ($birthTo !== '') {
    $sql .= " AND birthdate <= ?";
    $params[] = $birthTo;
}

if ($createdFrom !== '') {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $createdFrom;
}

if ($createdTo !== '') {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $createdTo;
}

$sql .= " ORDER BY name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$patients = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <div class="page-header">
        <h2>Pacientes</h2>
        <div class="page-actions">
            <button type="button" class="filter-toggle" onclick="toggleFilters()">🔍 Filtrar</button>
            <a href="patient_create.php" class="btn-primary">+ Novo Paciente</a>
        </div>
    </div>

    <div id="filters-panel" class="hidden">

    <form method="GET" class="filters-form">

    <div class="filter-row search-row">
    <div class="filter-field search-field"><label>Procura</label><input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nome, CC, Número de Utente, Telefone ou Email"></div>

    <div class="filter-field gender-field">
        <label>Género</label>
        <select name="gender">
            <option value="">Todos</option>
            <option value="Masculino" <?= $gender=="Masculino" ? "selected" : "" ?>>Homem</option>
            <option value="Feminino" <?= $gender=="Feminino" ? "selected" : "" ?>>Mulher</option>
            <option value="Não Binário" <?= $gender=="Não Binário" ? "selected" : "" ?>>Não Binário</option>
        </select>

    </div>

</div>

    <div class="filter-row two-cols">
        <div class="filter-field"><label>Data de Nascimento de</label><input type="date" name="birth_from" value="<?= htmlspecialchars($birthFrom) ?>"></div>
        <div class="filter-field"><label>Data de Nascimento até</label><input type="date" name="birth_to" value="<?= htmlspecialchars($birthTo) ?>"></div>
    </div>
    <div class="filter-row two-cols">
        <div class="filter-field"><label>Data de Criação de</label><input type="date" name="created_from" value="<?= htmlspecialchars($createdFrom) ?>"></div>
        <div class="filter-field"><label>Data de Criação até</label><input type="date" name="created_to" value="<?= htmlspecialchars($createdTo) ?>"></div>
    </div>
    <button type="submit">Filtrar</button>

</form>

</div>

    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>CC</th>
                <th>Utente</th>
                <th>Telefone</th>
                <th>Email</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>

        <?php if (count($patients) === 0): ?>

            <tr><td colspan="6">Nenhum paciente encontrado.</td></tr>

        <?php else: ?>
            <?php foreach ($patients as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>

                    <td><?= htmlspecialchars($p['citizen_card']) ?></td>

                    <td><?= htmlspecialchars($p['health_number']) ?></td>

                    <td><?= htmlspecialchars($p['phone']) ?></td>

                    <td><?= htmlspecialchars($p['email']) ?></td>

                    <td>
                        <a href="patient_details.php?id=<?= $p['id'] ?>">Ver</a> | <a href="patient_edit.php?id=<?= $p['id'] ?>">Editar</a>
                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>

<?php include "includes/footer.php"; ?>

<script>
function toggleFilters(){
    document
        .getElementById("filters-panel")
        .classList.toggle("hidden");
}
</script>