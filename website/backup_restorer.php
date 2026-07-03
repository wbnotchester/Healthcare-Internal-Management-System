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

if (!$backup || !isset($backup["patients"]) || !isset($backup["consultations"])){
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

$idMap = [];

try {
    $conn->beginTransaction();

    foreach ($backup["patients"] as $patient) {

        $check = $conn->prepare("
            SELECT id, citizen_card, health_number, social_security
            FROM patients
            WHERE
                citizen_card = ?
                OR health_number = ?
                OR (social_security IS NOT NULL AND social_security = ?)
            LIMIT 1
        ");

        $check->execute([
            $patient["citizen_card"],
            $patient["health_number"],
            $patient["social_security"]
        ]);

        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $idMap[$patient["id"]] = $existing["id"];

            $motivo = "citizen_card";
            if ($existing["citizen_card"] !== $patient["citizen_card"]) {
                $motivo = ($existing["health_number"] === $patient["health_number"])
                    ? "health_number"
                    : "social_security";
            }

            $log[] = "Paciente já existente ({$motivo}): " . $patient["name"];
            continue;
        }

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
            $patient["name"],
            $patient["birthdate"],
            $patient["gender"],
            $patient["citizen_card"],
            $patient["health_number"],
            $patient["social_security"],
            $patient["phone"],
            $patient["email"],
            $patient["address"]
        ]);

        $idMap[$patient["id"]] = (int)$conn->lastInsertId();

        $patientsRestored++;

        $log[] = "Paciente restaurado: ".$patient["name"];
    }

    foreach ($backup["consultations"] as $consultation) {

        $newPatientId = $idMap[$consultation["patient_id"]] ?? null;

        if ($newPatientId === null) {
            $log[] = "Consulta ignorada (paciente não encontrado no mapeamento)";
            continue;
        }

        $doctorStmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $doctorStmt->execute([
            $consultation["doctor_id"]
        ]);

        if (!$doctorStmt->fetch()) {
            $log[] = "Consulta ignorada (médico não encontrado)";
            continue;
        }

        $createdBy = null;

        if (!empty($consultation["created_by"])) {
            $creatorStmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE id = ?
                LIMIT 1
            ");

            $creatorStmt->execute([$consultation["created_by"]]);

            if ($creatorStmt->fetch()) {
                $createdBy = $consultation["created_by"];
            } else {
                $log[] = "created_by da consulta não encontrado, gravado como NULL";
            }
        }

        $checkConsult = $conn->prepare("
            SELECT id
            FROM consultations
            WHERE
                patient_id = ?
                AND doctor_id = ?
                AND consultation_date = ?
            LIMIT 1
        ");

        $checkConsult->execute([
            $newPatientId,
            $consultation["doctor_id"],
            $consultation["consultation_date"]
        ]);

        if ($checkConsult->fetch()) {
            $log[] = "Consulta duplicada ignorada";
            continue;
        }

        $stmt = $conn->prepare("
            INSERT INTO consultations
            (
                patient_id,
                doctor_id,
                created_by,
                diagnosis,
                notes,
                consultation_date,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $newPatientId,
            $consultation["doctor_id"],
            $createdBy,
            $consultation["diagnosis"],
            $consultation["notes"],
            $consultation["consultation_date"],
            $consultation["status"]
        ]);

        $consultationsRestored++;

        $log[] = "Consulta restaurada";
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
            "consultations_restored" => $consultationsRestored,
            "log" => $log
        ])
    );

    $_SESSION["success"] = "Backup restaurado com sucesso.";
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
            "error" => $e->getMessage(),
            "log" => $log
        ]),
        "CRITICAL"
    );
    die("Erro ao restaurar backup.");
}