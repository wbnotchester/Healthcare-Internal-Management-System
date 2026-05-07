<?php

function logEvent($message) {

    $file = __DIR__ . "/../logs/logs.txt";

    $log = date("Y-m-d H:i:s") .
           " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') .
           " | " . $message . PHP_EOL;

    file_put_contents($file, $log, FILE_APPEND);
}