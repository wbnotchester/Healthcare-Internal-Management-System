<?php
require "includes/db.php";
require "includes/auth.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user"] = $user;

        logEvent("Login sucesso: " . $username);

        header("Location: dashboard.php");
        exit();
    } else {
        logEvent("Login falhado: " . $username);
        echo "Credenciais inválidas";
    }
}
?>

<form method="POST">
    <input name="username" placeholder="Username" required>
    <input name="password" type="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>