<?php
require "includes/db.php";
require "includes/auth.php";

requireAuth();

/* apenas admin */
if ($_SESSION["user"]["role"] !== "Administrador") {
    die("Acesso negado");
}

/* utilizadores */
$stmt = $conn->query("
    SELECT id, username, email, role, mfa_enabled, created_at
    FROM users
    ORDER BY created_at DESC
");

$users = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>

<div class="card">
    <div class="page-header">
        <h2>Gestão de Utilizadores</h2><a href="user_create.php" class="btn-primary">+ Novo Utilizador</a>
    </div>

    <table class="table">

        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Função</th>
                <th>MFA</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>

        <?php if (count($users) === 0): ?>
            <tr><td colspan="6">Sem utilizadores.</td></tr>
        <?php else: ?>

            <?php foreach ($users as $u): ?>

                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                    <td>
                        <?php if ($u['mfa_enabled']): ?>
                            <span class="badge-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge-danger">Inativo</span>
                            <br><br>
                            <a href="send_mfa_reminder.php?id=<?= $u['id'] ?>" class="btn-warning">Enviar aviso</a>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= date("d/m/Y", strtotime($u['created_at'])) ?>
                    </td>
                    <td>
                        <a href="user_edit.php?id=<?= $u['id'] ?>">Editar</a>
                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>

<?php include "includes/footer.php"; ?>