<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/logger.php";

requireRole(["Administrador"]);

/**
 * Desencripta backup
 */
function decryptData($data, $key)
{
    $decoded = base64_decode($data);

    if (!$decoded || !str_contains($decoded, "::")) {
        return false;
    }

    list($iv, $encrypted) = explode("::", $decoded, 2);

    return openssl_decrypt(
        $encrypted,
        "AES-256-CBC",
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
}

/* =========================
   LISTAR BACKUPS
========================= */
$files = [];

if (is_dir("backups")) {
    $files = array_diff(scandir("backups"), ['.', '..']);
}

$log = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = trim($_POST["password"] ?? "");
    $file = basename($_POST["file"] ?? "");

    if (empty($password) || empty($file)) {
        die("Dados inválidos");
    }

    $filePath = "backups/" . $file;

    if (!file_exists($filePath)) {
        die("Ficheiro inválido");
    }

    //gerar chave igual ao backup
    $key = hash("sha256", $password, true);

    //ler conteúdo
    $content = file_get_contents($filePath);

    if ($content === false) {
        die("Erro ao ler ficheiro");
    }

    //desencriptar
    $decrypted = decryptData($content, $key);

    if (!$decrypted) {
        die("Password incorreta ou ficheiro corrompido");
    }

    //converter JSON
    $backup = json_decode($decrypted, true);

    if (!$backup || !isset($backup["data"])) {
        die("Backup inválido");
    }

    try {

        $conn->beginTransaction();

        foreach ($backup["data"] as $data) {

            /* =========================
               PATIENTS
            ========================= */

            $patientId = null;

            if (isset($data['citizen_card'])) {

                $check = $conn->prepare("
                    SELECT id
                    FROM patients
                    WHERE citizen_card = ?
                ");

                $check->execute([
                    $data['citizen_card']
                ]);

                $existingPatient = $check->fetch(PDO::FETCH_ASSOC);

                if ($existingPatient) {

                    $patientId = $existingPatient['id'];

                    $log[] = "Paciente já existente: " . $data['name'];

                } else {

                    $stmt = $conn->prepare("
                        INSERT INTO patients
                        (
                            name,
                            birthdate,
                            gender,
                            citizen_card,
                            health_number,
                            social_security,
                            phone,
                            email,
                            address
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $data['name'],
                        $data['birthdate'],
                        $data['gender'],
                        $data['citizen_card'],
                        $data['health_number'],
                        $data['social_security'],
                        $data['phone'],
                        $data['email'],
                        $data['address']
                    ]);

                    $patientId = $conn->lastInsertId();

                    $log[] = "Paciente restaurado: " . $data['name'];
                }
            }

            /* =========================
               CONSULTATIONS
            ========================= */

            if (
                !empty($data['consultation_id']) &&
                !empty($data['consultation_date'])
            ) {

                // 🔹 procurar médico
                $doctorStmt = $conn->prepare("
                    SELECT id
                    FROM users
                    WHERE username = ?
                    LIMIT 1
                ");

                $doctorStmt->execute([
                    $data['doctor_username']
                ]);

                $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);

                if (!$doctor) {

                    $log[] = "Consulta ignorada (médico não encontrado)";
                    continue;
                }

                $doctorId = $doctor['id'];

                // 🔹 verificar duplicados
                $checkConsult = $conn->prepare("
                    SELECT id
                    FROM consultations
                    WHERE patient_id = ?
                    AND consultation_date = ?
                ");

                $checkConsult->execute([
                    $patientId,
                    $data['consultation_date']
                ]);

                if ($checkConsult->fetch()) {

                    $log[] = "Consulta duplicada ignorada";

                } else {

                    $stmt = $conn->prepare("
                        INSERT INTO consultations
                        (
                            patient_id,
                            doctor_id,
                            diagnosis,
                            notes,
                            consultation_date,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $patientId,
                        $doctorId,
                        $data['diagnosis'],
                        $data['notes'],
                        $data['consultation_date'],
                        $data['status']
                    ]);

                    $log[] = "Consulta restaurada";
                }
            }
        }

        $conn->commit();

        logEvent("RESTORE EXECUTED | File: $file");

    } catch (Exception $e) {

        $conn->rollBack();

        logEvent("RESTORE ERROR | " . $e->getMessage());

        die("Erro ao restaurar backup");
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2>🔄 Restore Backup</h2>

    <form method="POST">

        <label>Ficheiro</label>

        <select name="file" required>

            <?php foreach ($files as $file): ?>
                <option value="<?= htmlspecialchars($file) ?>">
                    <?= htmlspecialchars($file) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit"> Restaurar</button>

    </form>

    <?php if (!empty($log)): ?>

        <h3>Resultado do Restore</h3>

        <ul>

            <?php foreach ($log as $l): ?>

                <li><?= htmlspecialchars($l) ?></li>

            <?php endforeach; ?>

        </ul>

    <?php endif; ?>

</div>

<?php include "includes/footer.php"; ?>