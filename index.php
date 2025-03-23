<?php

require __DIR__ . '/vendor/autoload.php'; 

use Pagnany\Kcp\Auth\Auth;

$isLoggedIn = Auth::isLoggedIn();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/style.css">
    <title>KCP - Home</title>
</head>
<body>
    <h1>Kegelclub Pegelbrüder</h1>

    <?php if ($isLoggedIn): ?>
        <nav>
            <ul>
                <li><a href="public/strafen.php">Strafen</a></li>
                <li><a href="public/mitglieder.php">Mitglieder</a></li>
                <li><a href="public/veranstaltungen.php">Veranstaltungen</a></li>
                <li><a href="public/statistiken.php">Statistiken</a></li>
            </ul>
        </nav>
    <?php else: ?>
        <a href="public/login.php">Login</a>
    <?php endif; ?>
</body>
</html>
