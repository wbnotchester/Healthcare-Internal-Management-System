<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/audit.php";

requireRole(["Administrador"]);

function decryptData(string $data, string $key): string|false{
    $decoded = base64_decode($data);

    if (!$decoded)
        return false;

    $parts = explode("::", $decoded, 2);

    if (count($parts) !== 2)
        return false;

    [$iv, $encrypted] = $parts;

    return openssl_decrypt($encrypted,"AES-256-CBC",$key,OPENSSL_RAW_DATA,$iv);
}

$log = [];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: backup.php");
    exit();
}

$backupId = (int)($_POST["backup_id"] ?? 0);
$password = trim($_POST["password"] ?? "");

if ($backupId <= 0 || $password === "") {
    die("Dados inválidos.");
}

$stmt = $conn->prepare("
    SELECT
        b.*,
        u.nome AS created_by_name
    FROM backups b

    JOIN users u
        ON u.id = b.created_by

    WHERE
        b.id = ?
        AND b.status = 'AVAILABLE'

    LIMIT 1
");

$stmt->execute([$backupId]);

$backupInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$backupInfo) {
    die("Backup não encontrado.");
}

if (!file_exists($backupInfo["file_path"])) {
    $stmt = $conn->prepare("
        UPDATE backups
        SET status='DELETED'
        WHERE id=?
    ");

    $stmt->execute([$backupId]);
    die("O ficheiro de backup já não existe.");
}

auditLog(
    $conn,
    "SYSTEM",
    "BACKUP_RESTORE_ATTEMPT",
    $backupInfo["file_name"],
    null,
    json_encode(["description" => "Tentativa de restauro de backup"])
);

$key = hash("sha256", $password, true);
$content = file_get_contents($backupInfo["file_path"]);

if ($content === false)
    die("Não foi possível ler o backup.");

$decrypted = decryptData($content,$key);

if ($decrypted === false) {
    auditLog(
        $conn,
        "SYSTEM",
        "BACKUP_RESTORE_FAILED",
        $backupInfo["file_name"],
        null,
        json_encode(["description" => "Password incorreta"]),
        "WARNING"
    );
    die("Password incorreta.");
}

$backup = json_decode($decrypted,true);

if (!$backup || !isset($backup["data"])) {
    auditLog(
        $conn,
        "SYSTEM",
        "BACKUP_RESTORE_FAILED",
        $backupInfo["file_name"],
        null,
        json_encode(["description" => "Backup inválido"]),
        "ERROR"
    );
    die("Backup inválido.");
}

$patientsRestored = 0;
$consultationsRestored = 0;

try {
    $conn->beginTransaction();
    foreach ($backup["data"] as $data) {
        $patientId = null;

        if (!empty($data["citizen_card"])) {
            $check = $conn->prepare("
                SELECT id
                FROM patients
                WHERE citizen_card = ?
                LIMIT 1
            ");

            $check->execute([$data["citizen_card"]]);
            $existingPatient = $check->fetch(PDO::FETCH_ASSOC);

            if ($existingPatient) {
                $patientId = $existingPatient["id"];
                $log[] = "Paciente já existente: " . $data["name"];

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
                    $data["name"],
                    $data["birthdate"],
                    $data["gender"],
                    $data["citizen_card"],
                    $data["health_number"],
                    $data["social_security"],
                    $data["phone"],
                    $data["email"],
                    $data["address"]

                ]);

                $patientId = $conn->lastInsertId();
                $patientsRestored++;
                $log[] = "Paciente restaurado: " . $data["name"];
            }
        }


        if (!empty($data["consultation_id"]) && !empty($data["consultation_date"])) {
            $doctorStmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $doctorStmt->execute([$data["doctor_username"]]);
            $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);

            if (!$doctor) {
                $log[] ="Consulta ignorada (médico não encontrado)";
                continue;
            }

            $doctorId = $doctor["id"];

            $checkConsult = $conn->prepare("
                SELECT id
                FROM consultations
                WHERE
                    patient_id = ?
                    AND consultation_date = ?
                LIMIT 1
            ");

            $checkConsult->execute([$patientId,$data["consultation_date"]]);

            if ($checkConsult->fetch()) {
                $log[]= "Consulta duplicada ignorada";

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
                    $data["diagnosis"],
                    $data["notes"],
                    $data["consultation_date"],
                    $data["status"]

                ]);

                $consultationsRestored++;

                $log[]= "Consulta restaurada";
            }

        }

    }

    $stmt = $conn->prepare("
        UPDATE backups
        SET
            last_restore_at = NOW(),
            last_restore_by = ?
        WHERE id = ?
    ");

    $stmt->execute([$_SESSION["user"]["id"],$backupId]);

    $conn->commit();

    auditLog(
        $conn,
        "SYSTEM",
        "BACKUP_RESTORE_SUCCESS",
        $backupInfo["file_name"],
        null,
        json_encode([
            "description" => "Backup restaurado com sucesso",
            "patients_restored" => $patientsRestored,
            "consultations_restored" => $consultationsRestored
        ])
    );

    $_SESSION["success"] ="Backup restored successfully.";
    header("Location: backup.php");
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    auditLog(
        $conn,
        "SYSTEM",
        "BACKUP_RESTORE_FAILED",
        $backupInfo["file_name"],
        null,
        json_encode([
            "description" => "Erro durante o restauro",
            "error" => $e->getMessage()
        ]),
        "CRITICAL"
    );
    die("Erro ao restaurar backup.");
}