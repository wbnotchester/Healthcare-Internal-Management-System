<!DOCTYPE html>
<html>
<head>
    <title>Meditrust Clinical Manager</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body>

<header class="main-header">
    
    <div class="left">
        <a href="dashboard.php" class="logo">
            <h2>Meditrust</h2>
        </a>

        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="consultations.php">Consultas</a>
        </nav>
    </div>

    <div class="right">
        <span class="user">
            <?= $_SESSION['user']['username'] ?? '' ?>
        </span>

        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

</header>

<div class="container">