<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Pagnany\Kcp\DatabaseConnect;
use Pagnany\Kcp\Auth\Auth;

$isLoggedIn = Auth::isLoggedIn();
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    try {
        $db = new DatabaseConnect();
        $conn = $db->getConnection();

        $bezeichnung = trim($_POST['bezeichnung'] ?? '');
        $preis = floatval($_POST['preis'] ?? 0);

        if (empty($bezeichnung)) {
            throw new Exception("Bezeichnung darf nicht leer sein.");
        }

        if ($preis <= 0) {
            throw new Exception("Preis muss größer als 0 sein.");
        }

        $stmt = $conn->prepare("INSERT INTO strafentyp (bezeichnung, preis, aktiv) VALUES (:bezeichnung, :preis, true)");
        $stmt->execute([
            ':bezeichnung' => $bezeichnung,
            ':preis' => $preis
        ]);

        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/style.css">
    <title>KCP - Strafe erstellen</title>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header>

    <main class="content">
        <h2>Neue Strafe erstellen</h2>

        <?php if (!$isLoggedIn): ?>
            <div class="error-message">
                Sie müssen eingeloggt sein, um eine neue Strafe zu erstellen.
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    Die Strafe wurde erfolgreich erstellt!
                </div>
            <?php endif; ?>

            <form method="POST" class="create-form">
                <div class="form-group">
                    <label for="bezeichnung">Bezeichnung:</label>
                    <input type="text" id="bezeichnung" name="bezeichnung" required>
                </div>

                <div class="form-group">
                    <label for="preis">Preis (€):</label>
                    <input type="number" id="preis" name="preis" step="0.01" min="0.01" required>
                </div>

                <div class="form-actions">
                    <button type="submit">Strafe erstellen</button>
                    <a href="/public/strafen_typ.php" class="button">Abbrechen</a>
                </div>
            </form>
        <?php endif; ?>
    </main>
</body>
</html> 