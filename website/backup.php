<?php
require "includes/db.php";
require "includes/auth.php";
require "includes/logger.php";

requireRole(["Administrador"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $exportPassword = $_POST["export_password"];

    if (strlen($exportPassword) < 6) {
        die("Password demasiado curta");
    }

    // 🔹 derivar chave forte a partir da password
    $key = hash("sha256", $exportPassword);

    function encryptData($data, $key) {

        $iv = openssl_random_pseudo_bytes(16);

        $encrypted = openssl_encrypt(
            $data,
            "AES-256-CBC",
            $key,
            0,
            $iv
        );

        return base64_encode($iv . "::" . $encrypted);
    }

    // 🔹 buscar dados
    global $conn;

    $stmt = $conn->query("
        SELECT p.*, c.diagnosis, c.notes, c.consultation_date
        FROM patients p
        LEFT JOIN consultations c ON c.patient_id = p.id
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $txt = "MEDITRUST BACKUP\n";
    $txt .= "DATE: " . date("Y-m-d H:i:s") . "\n\n";

    foreach ($data as $row) {
        $txt .= json_encode($row, JSON_PRETTY_PRINT) . "\n";
        $txt .= "-------------------------\n";
    }

    // 🔹 encriptar
    $encrypted = encryptData($txt, $key);

    $fileName = "backup_" . date("Y-m-d_H-i-s") . ".enc";

    file_put_contents("backups/" . $fileName, $encrypted);

    logEvent("BACKUP CREATED | File: $fileName");

    echo "Backup criado com sucesso!";
}
?>

<?php include "includes/header.php"; ?>

<div class="card">

    <h2>📦 Criar Backup Encriptado</h2>

    <form method="POST">

        <label>Password de Exportação</label>
        <input type="password" name="export_password" required>

        <button type="submit">Gerar Backup</button>

    </form>

</div>

<?php include "includes/footer.php"; ?>