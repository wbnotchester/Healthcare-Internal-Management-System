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

/* pesquisa */
$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $stmt = $conn->prepare("
        SELECT *
        FROM patients
        WHERE
            name LIKE ?
            OR citizen_card LIKE ?
            OR health_number LIKE ?
            OR phone LIKE ?
            OR email LIKE ?
        ORDER BY name ASC
    ");

    $term = "%$search%";

    $stmt->execute([
        $term,
        $term,
        $term,
        $term,
        $term
    ]);

} else {

    $stmt = $conn->query("
        SELECT *
        FROM patients
        ORDER BY name ASC
    ");
}

$patients = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <div class="page-header">

        <h2>Pacientes</h2>

        <a href="create_patient.php" class="btn">
            + Novo Paciente
        </a>

    </div>

    <form method="GET" class="search-form">

        <input
            type="text"
            name="search"
            placeholder="Pesquisar por nome, CC, utente, telefone..."
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit">
            Pesquisar
        </button>

        <?php if ($search): ?>
            <a href="patients.php" class="btn-secondary">
                Limpar
            </a>
        <?php endif; ?>

    </form>

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

            <tr>
                <td colspan="6">
                    Nenhum paciente encontrado.
                </td>
            </tr>

        <?php else: ?>
            <?php foreach ($patients as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>

                    <td><?= htmlspecialchars($p['citizen_card']) ?></td>

                    <td><?= htmlspecialchars($p['health_number']) ?></td>

                    <td><?= htmlspecialchars($p['phone']) ?></td>

                    <td><?= htmlspecialchars($p['email']) ?></td>

                    <td>
                        <a href="patient_details.php?id=<?= $p['id'] ?>">
                            Ver
                        </a>
                        |
                        <a href="edit_patient.php?id=<?= $p['id'] ?>">
                            Editar
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>

<?php include "includes/footer.php"; ?>