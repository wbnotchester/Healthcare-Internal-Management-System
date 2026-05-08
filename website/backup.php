<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/logger.php";

requireRole(["Administrador"]);

//Encripta dados usando AES-256-CBC
function encryptData($data, $key)
{
    $iv = openssl_random_pseudo_bytes(16);

    $encrypted = openssl_encrypt(
        $data,
        "AES-256-CBC",
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    return base64_encode($iv . "::" . $encrypted);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $exportPassword = trim($_POST["export_password"] ?? "");

    if (empty($exportPassword)) {
        die("Password obrigatória");
    }

    if (strlen($exportPassword) < 6) {
        die("Password demasiado curta");
    }

    //cria a pasta backups se não existir
    if (!is_dir("backups")) {
        mkdir("backups", 0777, true);
    }

    //derivar chave forte
    $key = hash("sha256", $exportPassword, true);

    try {

        /*
         * buscar dados completos
         * Evita SELECT * para maior controlo e segurança
         */
        $stmt = $conn->prepare("
            SELECT
                p.id AS patient_id,
                p.name,
                p.birthdate,
                p.gender,
                p.citizen_card,
                p.health_number,
                p.social_security,
                p.phone,
                p.email,
                p.address,
                p.created_at,

                c.id AS consultation_id,
                c.diagnosis,
                c.notes,
                c.consultation_date,
                c.status,

                u.nome AS doctor_name,
                u.username AS doctor_username

            FROM patients p

            LEFT JOIN consultations c
                ON c.patient_id = p.id

            LEFT JOIN users u
                ON c.doctor_id = u.id

            ORDER BY p.id, c.consultation_date DESC
        ");

        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //validar resultados
        if (!$data) {
            die("Nenhum dado encontrado para backup.");
        }

        //estrutura do backup
        $backup = [
            "system" => "MediTrust Clinical Manager",
            "backup_date" => date("Y-m-d H:i:s"),
            "generated_by" => $_SESSION["username"] ?? "Administrador",
            "total_records" => count($data),
            "data" => $data
        ];

        //converter para JSON formatado
        $json = json_encode(
            $backup,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            die("Erro ao gerar JSON do backup.");
        }

        //encriptar conteúdo
        $encrypted = encryptData($json, $key);

        //nome do ficheiro
        $fileName = "backup_" . date("Y-m-d_H-i-s") . ".enc";

        //guardar ficheiro
        $result = file_put_contents(
            "backups/" . $fileName,
            $encrypted
        );

        if ($result === false) {
            die("Erro ao guardar o ficheiro de backup.");
        }

        //logs
        logEvent("BACKUP CREATED | File: $fileName");

        $success = "Backup criado com sucesso!";

    } catch (PDOException $e) {

        logEvent("BACKUP ERROR | " . $e->getMessage());

        die("Erro ao gerar backup.");

    } catch (Exception $e) {

        logEvent("BACKUP GENERAL ERROR | " . $e->getMessage());

        die("Erro inesperado.");
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2>📦 Criar Backup Encriptado</h2>

    <?php if (!empty($success)): ?>
        <p style="color: green;">
            <?= htmlspecialchars($success) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Password de Exportação</label>
        <input type="password" name="export_password" minlength="6" required>
        <button type="submit">Gerar Backup</button>

    </form>

</div>

<?php include "includes/footer.php"; ?>