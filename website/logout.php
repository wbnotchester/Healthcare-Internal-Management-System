<?php
session_start();

require "includes/logger.php";

if (isset($_SESSION['user'])) {

    logEvent(
        "LOGOUT | User: " . $_SESSION['user']['username'] .
        " | ID: " . $_SESSION['user']['id']
    );
}

session_destroy();

header("Location: login.php");
exit();