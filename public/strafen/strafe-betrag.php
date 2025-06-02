<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Pagnany\Kcp\DatabaseConnect;
use Pagnany\Kcp\Auth\Auth;

$isLoggedIn = Auth::isLoggedIn();
$error = null;
$success = null;
$events = [];
$members = [];
$selectedEvent = null;
$penaltyName = '';

if (!$isLoggedIn) {
    header('Location: /public/login');
    exit;
}

try {
    $db = new DatabaseConnect();
    $conn = $db->getConnection();

    // Veranstaltungen laden
    $stmt = $conn->query("SELECT idveranstaltungen, titel, datumvon FROM veranstaltung ORDER BY datumvon DESC");
    $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Wenn Event gewählt, Mitglieder laden
    if (isset($_GET['event_id'])) {
        $selectedEvent = $_GET['event_id'];
        $stmt = $conn->prepare("SELECT idmitglieder, nickname, vorname, nachname FROM mitglieder WHERE aktiv = true ORDER BY vorname, nachname");
        $stmt->execute();
        $members = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Formularverarbeitung
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eventId = $_POST['event_id'] ?? null;
        $penaltyName = trim($_POST['penalty_name'] ?? '');
        $values = $_POST['values'] ?? [];
        $isAmount = $_POST['is_amount'] ?? [];
        $inDurchschnitt = $_POST['in_durchschnitt'] ?? [];

        if ($eventId && $penaltyName) {
            $conn->beginTransaction();
            try {
                foreach ($values as $memberId => $val) {
                    $val = str_replace(',', '.', $val);
                    $val = floatval($val);
                    if ($val > 0) {
                        $istAnzahl = (isset($isAmount[$memberId]) && $isAmount[$memberId] == 'anzahl') ? 1 : 0;
                        $inAvg = isset($inDurchschnitt[$memberId]) ? 1 : 0;
                        $stmt = $conn->prepare("INSERT INTO strafen (betrag, idveranstaltung, idmitglieder, grund, istanzahl, in_durchschnitt) VALUES (:betrag, :idveranstaltung, :idmitglieder, :grund, :istanzahl, :in_durchschnitt)");
                        $stmt->execute([
                            ':betrag' => $val,
                            ':idveranstaltung' => $eventId,
                            ':idmitglieder' => $memberId,
                            ':grund' => $penaltyName,
                            ':istanzahl' => $istAnzahl,
                            ':in_durchschnitt' => $inAvg
                        ]);
                    }
                }
                $conn->commit();
                $success = "Strafen wurden erfolgreich eingetragen!";
            } catch (\Exception $e) {
                $conn->rollBack();
                $error = "Fehler beim Eintragen der Strafen: " . $e->getMessage();
            }
        }
    }
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
    <title>KCP - Strafe (Betrag/Anzahl) eintragen</title>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header>
    <h2>Strafe (Betrag/Anzahl) eintragen</h2>
    <nav>
        <ul>
            <li><a href="strafen">Zurück zur Übersicht</a></li>
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
        <form method="GET" class="event-form">
            <div class="form-group">
                <label for="event_id">Veranstaltung:</label>
                <select id="event_id" name="event_id" required>
                    <option value="">Bitte wählen...</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?= $event['idveranstaltungen'] ?>" <?= $selectedEvent == $event['idveranstaltungen'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['titel']) ?> (<?= date('d.m.Y', strtotime($event['datumvon'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="submit-button">Laden</button>
            </div>
        </form>

        <?php if ($selectedEvent && !empty($members)): ?>
            <form method="POST" class="event-form">
                <input type="hidden" name="event_id" value="<?= $selectedEvent ?>">
                <div class="form-group">
                    <label for="penalty_name">Strafenname:</label>
                    <input type="text" id="penalty_name" name="penalty_name" value="<?= htmlspecialchars($penaltyName) ?>" required>
                </div>
                <div class="attendance-list">
                    <?php foreach ($members as $member): ?>
                        <div class="attendance-item">
                            <label class="member-name">
                                <?= htmlspecialchars($member['vorname']) ?> <?= htmlspecialchars($member['nachname']) ?> (<?= htmlspecialchars($member['nickname']) ?>)
                            </label>
                            <div class="attendance-options">
                                <input type="number" step="0.01" name="values[<?= $member['idmitglieder'] ?>]" min="0" value="0" style="width:80px;">
                                <label style="margin-left:10px;">
                                    <input type="radio" name="is_amount[<?= $member['idmitglieder'] ?>]" value="betrag" checked> Betrag (€)
                                </label>
                                <label style="margin-left:10px;">
                                    <input type="radio" name="is_amount[<?= $member['idmitglieder'] ?>]" value="anzahl"> Anzahl
                                </label>
                                <label style="margin-left:20px;">
                                    <input type="checkbox" name="in_durchschnitt[<?= $member['idmitglieder'] ?>]" value="1">
                                    In Durchschnitt
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-group">
                    <button type="submit" class="submit-button">Strafen speichern</button>
                </div>
            </form>
        <?php elseif ($selectedEvent): ?>
            <p>Keine Mitglieder gefunden.</p>
        <?php endif; ?>
    </div>
</body>
</html>
