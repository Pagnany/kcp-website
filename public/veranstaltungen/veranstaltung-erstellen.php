<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Pagnany\Kcp\DatabaseConnect;
use Pagnany\Kcp\Auth\Auth;

$isLoggedIn = Auth::isLoggedIn();
$error = null;
$success = null;

if (!$isLoggedIn) {
    header('Location: /public/login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new DatabaseConnect();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("INSERT INTO veranstaltung (titel, beschreibung, datumvon, datumbis) VALUES (:titel, :beschreibung, :datumvon, :datumbis)");
        
        $stmt->execute([
            ':titel' => $_POST['titel'],
            ':beschreibung' => $_POST['beschreibung'],
            ':datumvon' => $_POST['datumvon'],
            ':datumbis' => !empty($_POST['datumbis']) ? $_POST['datumbis'] : null
        ]);

        $success = "Veranstaltung wurde erfolgreich erstellt!";
    } catch (\PDOException $e) {
        $error = "Datenbankfehler: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/style.css">
    <title>KCP - Veranstaltung erstellen</title>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header> 

    <h2>Veranstaltung erstellen</h2>
    <nav>
        <ul>
            <li><a href="veranstaltungen">Zurück zur Übersicht</a></li>
        </ul>
    </nav>

    <?php if ($error): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="" class="event-form">
            <div class="form-group">
                <label for="titel">Titel:</label>
                <input type="text" id="titel" name="titel" required>
            </div>

            <div class="form-group">
                <label for="beschreibung">Beschreibung:</label>
                <textarea id="beschreibung" name="beschreibung" rows="5" required></textarea>
            </div>

            <div class="form-group">
                <label for="datumvon">Datum von:</label>
                <input type="date" id="datumvon" name="datumvon" required>
            </div>

            <div class="form-group">
                <label for="datumbis">Datum bis (optional):</label>
                <input type="date" id="datumbis" name="datumbis">
            </div>

            <div class="form-group">
                <button type="submit" class="submit-button">Veranstaltung erstellen</button>
            </div>
        </form>
    </div>
</body>
</html> 