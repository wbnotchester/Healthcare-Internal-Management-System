<?php

function auditLog(
    PDO $conn,
    string $logType,
    string $action,
    ?string $entityName = null,
    ?int $entityId = null,
    ?string $details = null,
    string $severity = 'INFO',
    ?int $userId = null,
    ?string $username = null
): void {

    //Se não forem fornecidos, tenta obter da sessão
    $userId ??= $_SESSION['user']['id'] ?? null;
    $username ??= $_SESSION['user']['username'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO audit_logs (
            user_id,
            username,
            severity,
            log_type,
            action,
            entity_name,
            entity_id,
            details
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $username,
        $severity,
        $logType,
        $action,
        $entityName,
        $entityId,
        $details
    ]);
}