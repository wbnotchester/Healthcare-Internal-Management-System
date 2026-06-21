<?php

function auditLog(
    PDO $conn,
    string $action,
    string $entity = '',
    ?int $entityId = null,
    ?string $details = null,
    string $severity = 'INFO'
): void {

    $userId = $_SESSION['user']['id'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO audit_logs (
            user_id,
            severity,
            action,
            entity,
            entity_id,
            details
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $severity,
        $action,
        $entity,
        $entityId,
        $details
    ]);
}