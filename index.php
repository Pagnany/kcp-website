<?php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

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
    <header class="site-header">
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header> 

    <h1>Kegelclub Pegelbrüder</h1>

    <?php if ($isLoggedIn): ?>
        <nav>
            <ul>
                <li><a href="public/strafen/strafen">Strafen</a></li>
                <li><a href="public/mitglieder">Mitglieder</a></li>
                <li><a href="public/veranstaltungen/veranstaltungen">Veranstaltungen</a></li>
                <li><a href="public/statistiken">Statistiken</a></li>
            </ul>
        </nav>
    <?php else: ?>
        <a href="public/login">Login</a>
    <?php endif; ?>
</body>
</html>
