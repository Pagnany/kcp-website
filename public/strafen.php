<?php
require __DIR__ . '/../vendor/autoload.php';

use Pagnany\Kcp\Auth\Auth;

$isLoggedIn = Auth::isLoggedIn();
define('INCLUDED', true);
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
    <?php include __DIR__ . '/includes/header.php'; ?>

    <h2>Strafen</h2>
    <?php if ($isLoggedIn): ?>
        <nav>
            <ul>
                <li><a href="/public/strafen/kegelstrafen.php">Kegelstrafen</a></li>
            </ul>
        </nav>
    <?php endif; ?>
</body>
</html>
