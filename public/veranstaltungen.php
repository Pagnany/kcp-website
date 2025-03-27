<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Pagnany\Kcp\DatabaseConnect;
use Pagnany\Kcp\Auth\Auth;

$isLoggedIn = Auth::isLoggedIn();
$error = null;
$events = [];

try {
    $db = new DatabaseConnect();
    $conn = $db->getConnection();

    // Fetch events from database
    $stmt = $conn->query("SELECT idveranstaltungen, titel, beschreibung, datumvon, datumbis FROM veranstaltung ORDER BY datumvon DESC");
    $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $error = "Datenbankfehler: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/style.css">
    <title>KCP - Veranstaltungen</title>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/index.php">Kegelclub Pegelbrüder</a></h1>
    </header> 

    <h2>Veranstaltungen</h2>
    <?php if ($isLoggedIn): ?>
        <nav>
            <ul>
                <li><a href="veranstaltungen/veranstaltung-erstellen.php">Veranstaltung erstellen</a></li>
            </ul>
        </nav>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="events-container">
        <?php if (empty($events)): ?>
            <p>Keine Veranstaltungen gefunden.</p>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <h3><?= htmlspecialchars($event['titel']) ?></h3>
                    <p class="event-description"><?= nl2br(htmlspecialchars($event['beschreibung'])) ?></p>
                    <p class="event-date">
                        Von: <?= date('d.m.Y', strtotime($event['datumvon'])) ?>
                        <?php if ($event['datumbis']): ?>
                            Bis: <?= date('d.m.Y', strtotime($event['datumbis'])) ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
