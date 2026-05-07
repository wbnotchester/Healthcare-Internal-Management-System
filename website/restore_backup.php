<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/logger.php";

requireRole(["Administrador"]);

function decryptData($data, $key) {

    $decoded = base64_decode($data);

    list($iv, $encrypted) = explode("::", $decoded, 2);

    return openssl_decrypt(
        $encrypted,
        "AES-256-CBC",
        $key,
        0,
        $iv
    );
}

/* =========================
   LISTAR BACKUPS DISPONÍVEIS
========================= */
$files = array_diff(scandir("backups"), array('.', '..'));

$log = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $password = $_POST["password"];
    $file = $_POST["file"];

    $filePath = "backups/" . basename($file);

    if (!file_exists($filePath)) {
        die("Ficheiro inválido");
    }

    $key = hash("sha256", $password);

    $content = file_get_contents($filePath);

    $decrypted = decryptData($content, $key);

    if (!$decrypted) {
        die("Password incorreta ou ficheiro corrompido");
    }

    $lines = explode("-------------------------", $decrypted);

    foreach ($lines as $block) {

        $block = trim($block);
        if (empty($block)) continue;

        preg_match_all('/\{.*\}/s', $block, $matches);

        if (!isset($matches[0][0])) continue;

        $data = json_decode($matches[0][0], true);
        if (!$data) continue;

        /* =========================
           PATIENTS
        ==========================*/
        if (isset($data['citizen_card'])) {

            $check = $conn->prepare("
                SELECT id FROM patients WHERE citizen_card = ?
            ");
            $check->execute([$data['citizen_card']]);

            if ($check->fetch()) {
                $log[] = "Paciente ignorado (duplicado): " . $data['name'];
            } else {

                $stmt = $conn->prepare("
                    INSERT INTO patients
                    (name, birthdate, citizen_card, health_number, phone, email, address)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $data['name'],
                    $data['birthdate'],
                    $data['citizen_card'],
                    $data['health_number'],
                    $data['phone'],
                    $data['email'],
                    $data['address']
                ]);

                $log[] = "Paciente inserido: " . $data['name'];
            }
        }

        /* =========================
           CONSULTATIONS
        ==========================*/
        if (isset($data['diagnosis'])) {

            $check = $conn->prepare("
                SELECT id FROM consultations
                WHERE patient_id = ?
                AND consultation_date = ?
            ");

            $check->execute([
                $data['patient_id'],
                $data['consultation_date']
            ]);

            if ($check->fetch()) {
                $log[] = "Consulta ignorada (duplicada)";
            } else {

                $stmt = $conn->prepare("
                    INSERT INTO consultations
                    (patient_id, doctor_id, diagnosis, notes, consultation_date, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $data['patient_id'],
                    $data['doctor_id'],
                    $data['diagnosis'],
                    $data['notes'],
                    $data['consultation_date'],
                    $data['status'] ?? 'Agendada'
                ]);

                $log[] = "Consulta inserida";
            }
        }
    }

    logEvent("RESTORE EXECUTED | File: $file");
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

        <button type="submit">Restaurar</button>

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