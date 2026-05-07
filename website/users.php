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

        <h2>Gestão de Utilizadores</h2>

        <a href="create_user.php" class="btn">
            + Novo Utilizador
        </a>

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

            <tr>
                <td colspan="6">
                    Sem utilizadores.
                </td>
            </tr>

        <?php else: ?>

            <?php foreach ($users as $u): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($u['username']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($u['email']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($u['role']) ?>
                    </td>

                    <td>

                        <?php if ($u['mfa_enabled']): ?>
                            <span style="color:green;">Ativo</span>
                        <?php else: ?>
                            <span style="color:red;">Inativo</span>
                        <?php endif; ?>

                    </td>

                    <td>
                        <?= date("d/m/Y", strtotime($u['created_at'])) ?>
                    </td>

                    <td>

                        <a href="edit_user.php?id=<?= $u['id'] ?>">
                            Editar
                        </a>

                        |

                        <a href="setup_mfa.php?id=<?= $u['id'] ?>">
                            MFA
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>

<?php include "includes/footer.php"; ?>