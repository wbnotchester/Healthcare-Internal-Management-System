<?php

require_once __DIR__ . "/googleAuthenticator.php";

/*Gera um secret MFA (TOTP)*/
function mfa_generate_secret() {
    $ga = new PHPGangsta_GoogleAuthenticator();
    return $ga->createSecret();
}

/*Gera URL QR Code para Google Authenticator */
function mfa_get_qr($username, $secret, $issuer = "HIMS") {
    $ga = new PHPGangsta_GoogleAuthenticator();

    return $ga->getQRCodeGoogleUrl(
        $username,
        $secret,
        $issuer
    );
}

/*Verifica código MFA (TOTP)*/
function mfa_verify_code($secret, $code) {
    $ga = new PHPGangsta_GoogleAuthenticator();

    return $ga->verifyCode($secret, $code, 2);
}

/*Ativar MFA para utilizador*/
function mfa_enable_user($conn, $user_id, $secret) {
    $stmt = $conn->prepare("
        UPDATE users 
        SET mfa_enabled = 1, mfa_secret = ?
        WHERE id = ?
    ");

    return $stmt->execute([$secret, $user_id]);
}

/* Desativar MFA*/
function mfa_disable_user($conn, $user_id) {
    $stmt = $conn->prepare("
        UPDATE users 
        SET mfa_enabled = 0, mfa_secret = NULL
        WHERE id = ?
    ");

    return $stmt->execute([$user_id]);
}

/*Marca sessão como autenticada*/
function mfa_mark_authenticated($user) {
    $_SESSION["user"] = $user;
    $_SESSION["mfa_passed"] = true;

    unset($_SESSION['mfa_user_id']);
    unset($_SESSION['mfa_required']);
}