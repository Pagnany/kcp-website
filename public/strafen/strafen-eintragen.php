<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Pagnany\Kcp\DatabaseConnect;
use Pagnany\Kcp\Auth\Auth;

$isLoggedIn = Auth::isLoggedIn();
$error = null;
$success = null;
$events = [];
$penaltyTypes = [];
$members = [];
$selectedEvent = null;
$selectedPenaltyType = null;

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

    // Strafentypen laden
    $stmt = $conn->query("SELECT id, bezeichnung, preis FROM strafentyp WHERE aktiv = true ORDER BY bezeichnung");
    $penaltyTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Wenn Event und Strafentyp gewählt, anwesende Mitglieder laden
    if (isset($_GET['event_id']) && isset($_GET['penalty_type_id'])) {
        $selectedEvent = $_GET['event_id'];
        $selectedPenaltyType = $_GET['penalty_type_id'];
        $stmt = $conn->prepare("SELECT m.idmitglieder, m.nickname, m.vorname, m.nachname FROM mitglieder m JOIN anwesenheit a ON m.idmitglieder = a.id_mitglied WHERE a.id_veranstaltung = :event_id AND (a.anwesend = 1 OR a.spaeter = 1) AND m.aktiv = true ORDER BY m.vorname, m.nachname");
        $stmt->execute([':event_id' => $selectedEvent]);
        $members = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Formularverarbeitung
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eventId = $_POST['event_id'] ?? null;
        $penaltyTypeId = $_POST['penalty_type_id'] ?? null;
        $penalties = $_POST['penalties'] ?? [];

        if ($eventId && $penaltyTypeId) {
            $conn->beginTransaction();
            try {
                foreach ($penalties as $memberId => $anzahl) {
                    $anzahl = intval($anzahl);
                    if ($anzahl > 0) {
                        $inDurchschnitt = isset($_POST['in_durchschnitt'][$memberId]) ? 1 : 0;
                        $stmt = $conn->prepare("INSERT INTO strafen (betrag, idveranstaltung, idmitglieder, idstrafentyp, grund, istanzahl, in_durchschnitt) VALUES (:betrag, :idveranstaltung, :idmitglieder, :idstrafentyp, '', 1, :in_durchschnitt)");
                        $stmt->execute([
                            ':betrag' => $anzahl,
                            ':idveranstaltung' => $eventId,
                            ':idmitglieder' => $memberId,
                            ':idstrafentyp' => $penaltyTypeId,
                            ':in_durchschnitt' => $inDurchschnitt
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
    <title>KCP - Strafen eintragen</title>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header> 

    <h2>Strafen eintragen</h2>
    <nav>
        <ul>
            <li><a href="../strafen/strafen">Zurück zur Übersicht</a></li>
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
            </div>
            <div class="form-group">
                <label for="penalty_type_id">Strafentyp:</label>
                <select id="penalty_type_id" name="penalty_type_id" required>
                    <option value="">Bitte wählen...</option>
                    <?php foreach ($penaltyTypes as $type): ?>
                        <option value="<?= $type['id'] ?>" <?= $selectedPenaltyType == $type['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type['bezeichnung']) ?> (<?= number_format($type['preis'], 2, ',', '.') ?> €)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="submit-button">Laden</button>
        </form>

        <?php if ($selectedEvent && $selectedPenaltyType && !empty($members)): ?>
            <form method="POST" class="event-form">
                <input type="hidden" name="event_id" value="<?= $selectedEvent ?>">
                <input type="hidden" name="penalty_type_id" value="<?= $selectedPenaltyType ?>">
                <div class="attendance-list">
                    <?php foreach ($members as $member): ?>
                        <div class="attendance-item">
                            <label class="member-name">
                                <?= htmlspecialchars($member['vorname']) ?> <?= htmlspecialchars($member['nachname']) ?> (<?= htmlspecialchars($member['nickname']) ?>)
                            </label>
                            <div class="attendance-options">
                                <input type="number" name="penalties[<?= $member['idmitglieder'] ?>]" min="0" value="0" style="width:60px;">
                                <span>Anzahl</span>
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
        <?php elseif ($selectedEvent && $selectedPenaltyType): ?>
            <p>Keine anwesenden Mitglieder für diese Veranstaltung gefunden.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<!--
Datenbankstruktur:
mitglieder(idmitglieder 	username 	passwort 	nickname 	vorname 	nachname 	gebdatum 	beschreibung 	aktiv)
veranstaltung(idveranstaltungen 	titel 	beschreibung 	datumvon 	datumbis)
anwesenheit(id 	id_veranstaltung 	id_mitglied 	anwesend 	kommentar)
strafentyp(id 	bezeichnung 	preis 	aktiv)
strafen(idstrafen idstrafentyp 	betrag 	idveranstaltung 	idmitglieder 	grund 	istanzahl 	in_durchschnitt)
-->