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
    <link rel="stylesheet" href="/public/style.css">
    <title>KCP - Strafen</title>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header> 

    <h2>Strafen</h2>
    <?php if ($isLoggedIn): ?>
        <nav>
            <ul>
                <li><a href="strafen-typ">Strafen Typ</a></li>
                <li><a href="kegelstrafen">Kegelstrafen</a></li>
            </ul>
        </nav>
    <?php endif; ?>
</body>
</html>
