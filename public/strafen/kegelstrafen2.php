<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Pagnany\Kcp\DatabaseConnect;

$events = [];
$presentMembers = [];
$absentMembers = [];
$selectedEvent = null;
$penaltyTypes = [];
$memberPenalties = []; // Array für Strafen pro Mitglied

// Veranstaltung aus GET-Parameter auslesen
if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
    $selectedEvent = $_GET['event_id'];
}

try {
    $db = new DatabaseConnect();
    $conn = $db->getConnection();
    
    // Veranstaltungen laden
    $stmt = $conn->query("SELECT idveranstaltungen, titel, datumvon FROM veranstaltung ORDER BY datumvon DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Wenn eine Veranstaltung ausgewählt wurde
    if ($selectedEvent) {
        // Anwesenheit Mitglieder
        $stmt = $conn->prepare("
            SELECT 
                mitglieder.idmitglieder, 
                mitglieder.vorname, 
                mitglieder.nachname, 
                anwesenheit.anwesend 
            FROM anwesenheit
            LEFT JOIN mitglieder ON anwesenheit.id_mitglied = mitglieder.idmitglieder
            WHERE anwesenheit.id_veranstaltung = :event_id
            ORDER BY mitglieder.vorname
        ");
        $stmt->execute([':event_id' => $selectedEvent]);
        $anwesenheit = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // alle Strafen für Mitglieder
        $stmt = $conn->prepare("
            SELECT 
                mitglieder.vorname, 
                strafen.betrag, 
                strafen.grund, 
                strafen.istanzahl, 
                strafen.in_durchschnitt, 
                strafentyp.bezeichnung, 
                strafentyp.preis 
            FROM strafen
            LEFT JOIN mitglieder ON strafen.idmitglieder = mitglieder.idmitglieder
            LEFT JOIN strafentyp ON strafentyp.id = strafen.idstrafentyp
            WHERE strafen.idveranstaltung = :event_id
            ORDER BY mitglieder.vorname, strafen.idstrafentyp DESC
        ");
        $stmt->execute([':event_id' => $selectedEvent]);
        $strafen_mitglieder = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Alle passierten Strafen
        $stmt = $conn->prepare("
            SELECT DISTINCT 
                strafen.idstrafentyp, 
                strafen.grund, 
                strafentyp.bezeichnung, 
                strafentyp.preis 
            FROM strafen 
            LEFT JOIN strafentyp ON strafen.idstrafentyp = strafentyp.id 
            WHERE strafen.idveranstaltung = :event_id
        ");
        $stmt->execute([':event_id' => $selectedEvent]);
        $strafen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo 'Datenbankfehler: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/style.css">
    <title>KCP - Kegelstrafen</title>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header> 
    <h1>Kegelstrafen</h1>
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