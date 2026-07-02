<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/audit.php";

requireRole(["Administrador"]);

function encryptData(string $data, string $key): string{
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt(
        $data,
        "AES-256-CBC",
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
    return base64_encode($iv . "::" . $encrypted);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: backup.php");
    exit();
}

$password = trim($_POST["password"] ?? "");

if (strlen($password) < 6) {
    die("A password with at least 6 characters is required.");
}

/* Auditoria */
auditLog(
    $conn,
    "SYSTEM",
    "BACKUP_CREATE_ATTEMPT",
    null,
    null,
    json_encode(["description" => "Iniciada criação de backup"])
);

if (!is_dir("backups")) {
    mkdir("backups", 0777, true);
}

$key = hash("sha256", $password, true);

try {

    $stmt = $conn->query("
        SELECT
            id,
            name,
            birthdate,
            gender,
            citizen_card,
            health_number,
            social_security,
            phone,
            email,
            address,
            created_at
        FROM patients
        ORDER BY id
    ");

    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("
        SELECT
            c.id,
            c.patient_id,
            c.doctor_id,
            c.diagnosis,
            c.notes,
            c.consultation_date,
            c.status,
            u.username AS doctor_username
        FROM consultations c

        LEFT JOIN users u
            ON u.id = c.doctor_id

        ORDER BY c.consultation_date
    ");

    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPatients = count($patients);
    $totalConsultations = count($consultations);

    $backup = [
        "system" => "HealthCare Internal Management System",
        "backup_date" => date("Y-m-d H:i:s"),
        "generated_by" => $_SESSION["user"]["username"],
        "patients" => $patients,
        "consultations" => $consultations
    ];

    $json = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        throw new Exception("Incapaz de gerar JSON de backup.");
    }

    $encrypted = encryptData($json,$key);

    $fileName = "backup_".date("Y-m-d_H-i-s") .".enc";

    $filePath = "backups/".$fileName;

    if (file_put_contents($filePath,$encrypted) === false) {
        throw new Exception("Incapaz de salvar o arquivo de backup.");
    }

    $fileSize = filesize($filePath);

    $sha256 = hash_file("sha256",$filePath);

    $totalRecords = $totalPatients + $totalConsultations;

    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO backups
        (
            file_name,
            file_path,
            created_by,
            file_size,
            sha256,
            total_patients,
            total_consultations,
            total_records,
            encryption_algorithm,
            status
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $fileName,
        $filePath,
        $_SESSION["user"]["id"],
        $fileSize,
        $sha256,
        $totalPatients,
        $totalConsultations,
        $totalRecords,
        "AES-256-CBC",
        "AVAILABLE"
    ]);

    $conn->commit();

    auditLog(
        $conn,
        "SYSTEM",
        "BACKUP_CREATE_SUCCESS",
        $fileName,
        null,
        json_encode([
            "description" => "Backup criado com sucesso",
            "patients" => $totalPatients,
            "consultations" => $totalConsultations,
            "records" => $totalRecords,
            "sha256" => $sha256,
            "size" => $fileSize
        ])

    );

    $_SESSION["success"] = "Backup criado com sucesso.";

    header("Location: backup.php");

    exit();

} catch (Exception $e) {
    if ($conn->inTransaction())
        $conn->rollBack();

    if (isset($filePath) && file_exists($filePath))
        unlink($filePath);

    auditLog(
        $conn,
        "SYSTEM",
        "BACKUP_CREATE_FAILED",
        $fileName ?? null,
        null,
        json_encode([
            "description" => "Falha ao criar backup",
            "error" => $e->getMessage()
        ]),
        "CRITICAL"
    );

    die("Incapaz de criar backup.");
}