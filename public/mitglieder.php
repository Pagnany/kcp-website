<?php
require __DIR__ . '/../vendor/autoload.php';

use Pagnany\Kcp\DatabaseConnect;
use Pagnany\Kcp\Auth\Auth;

$isLoggedIn = Auth::isLoggedIn();
$error = null;
$members = [];

try {
    $db = new DatabaseConnect();
    $conn = $db->getConnection();

    // Fetch members from database
    $stmt = $conn->query("SELECT idmitglieder, nickname, vorname, nachname FROM mitglieder ORDER BY nachname, vorname");
    $members = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $error = "Datenbankfehler: " . $e->getMessage();
}

define('INCLUDED', true);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/style.css">
    <title>KCP - Mitglieder</title>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <h2>Mitglieder</h2>
    <?php if ($isLoggedIn): ?>
        <nav>
            <ul>
                <li><a href="mitglieder/mitglied-erstellen.php">Mitglied erstellen</a></li>
            </ul>
        </nav>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="members-container">
        <?php if (empty($members)): ?>
            <p>Keine Mitglieder gefunden.</p>
        <?php else: ?>
            <div class="members-list">
                <?php foreach ($members as $member): ?>
                    <div class="member-item">
                        <div class="member-info">
                            <h3><?= htmlspecialchars($member['nickname']) ?></h3>
                            <p class="member-name">
                                <?= htmlspecialchars($member['vorname']) ?> 
                                <?= htmlspecialchars($member['nachname']) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 