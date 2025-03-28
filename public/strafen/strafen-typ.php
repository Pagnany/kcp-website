<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Pagnany\Kcp\DatabaseConnect;
use Pagnany\Kcp\Auth\Auth;

$isLoggedIn = Auth::isLoggedIn();
$error = null;
$penaltyTypes = [];

try {
    $db = new DatabaseConnect();
    $conn = $db->getConnection();

    // Fetch penalty types from database
    $stmt = $conn->query("SELECT id, bezeichnung, preis FROM strafentyp WHERE aktiv = true ORDER BY bezeichnung");
    $penaltyTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
    <title>KCP - Strafen</title>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header> 

    <div class="simple-container">
        <h2>Strafen</h2>
        <?php if ($isLoggedIn): ?>
            <nav>
                <ul>
                    <li><a href="strafe-erstellen">Strafe erstellen</a></li>
                </ul>
            </nav>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="penalties-container">
            <?php if (empty($penaltyTypes)): ?>
                <div class="penalties-list">
                    <p>Keine Strafen gefunden.</p>
                </div>
            <?php else: ?>
                <div class="penalties-list">
                    <?php foreach ($penaltyTypes as $penalty): ?>
                        <div class="penalty-item">
                            <div class="penalty-info">
                                <h3><?= htmlspecialchars($penalty['bezeichnung']) ?></h3>
                                <p class="penalty-price">
                                    <?= number_format($penalty['preis'], 2, ',', '.') ?> €
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 