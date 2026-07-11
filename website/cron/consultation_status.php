<?php

require_once __DIR__ . "/../audit.php";

function updateConsultationStatus(PDO $conn): void {
    $stmt = $conn->prepare("
        SELECT id
        FROM consultations
        WHERE status = 'Agendada'
          AND consultation_date <= NOW()
    ");

    $stmt->execute();
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($consultations))
        return;

    $update = $conn->prepare("
        UPDATE consultations
        SET status = 'Pendente'
        WHERE id = ?
    ");

    foreach ($consultations as $consultation) {

        $update->execute([$consultation["id"]]);

        auditLog(
            conn: $conn,
            logType: "APPOINTMENT",
            action: "APPOINTMENT_UPDATE",
            entityName: "consultations",
            entityId: $consultation["id"],
            details: "Estado atualizado de 'Agendada' para 'Pendente' pela rotina de manutenção.",
            severity: "INFO",
            userId: null,
            username: "SYSTEM"
        );
    }
}