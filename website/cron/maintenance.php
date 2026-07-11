<?php

require_once __DIR__ . "/cron/consultation_status.php";

function runMaintenance(PDO $conn): void{
    updateConsultationStatus($conn);
}