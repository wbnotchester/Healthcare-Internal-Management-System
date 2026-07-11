<?php

require_once __DIR__ . "/maintenance/consultation_status.php";

function runMaintenance(PDO $conn): void{
    updateConsultationStatus($conn);
}