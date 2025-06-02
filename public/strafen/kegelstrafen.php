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
        // Alle Mitglieder laden
        $stmt = $conn->query("SELECT idmitglieder, nickname, vorname, nachname FROM mitglieder WHERE aktiv = true ORDER BY vorname, nachname");
        $allMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Anwesenheitsdaten für die gewählte Veranstaltung laden
        $stmt = $conn->prepare("SELECT id_mitglied, anwesend FROM anwesenheit WHERE id_veranstaltung = :event_id");
        $stmt->execute([':event_id' => $selectedEvent]);
        $attendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Strafentypen laden
        $stmt = $conn->query("SELECT id, bezeichnung, preis FROM strafentyp WHERE aktiv = true ORDER BY bezeichnung");
        $penaltyTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Strafen für diese Veranstaltung laden
        $stmt = $conn->prepare("
            SELECT s.idstrafen, s.idstrafentyp, s.betrag, s.idmitglieder, s.grund, s.istanzahl, s.in_durchschnitt,
                   st.bezeichnung AS strafentyp_bezeichnung, st.preis
            FROM strafen s
            LEFT JOIN strafentyp st ON s.idstrafentyp = st.id
            WHERE s.idveranstaltung = :event_id
        ");
        $stmt->execute([':event_id' => $selectedEvent]);
        $penalties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Strafen nach Mitgliedern organisieren
        foreach ($penalties as $penalty) {
            $memberId = $penalty['idmitglieder'];
            if (!isset($memberPenalties[$memberId])) {
                $memberPenalties[$memberId] = [];
            }
            $memberPenalties[$memberId][] = $penalty;
        }
        
        // Arrays für Anwesende und Abwesende initialisieren
        $presentMembers = [];
        $absentMembers = [];
        
        // Mitglieder nach Anwesenheit filtern und Strafen berechnen
        foreach ($allMembers as $member) {
            $id = $member['idmitglieder'];
            // Strafen für dieses Mitglied hinzufügen
            $member['penalties'] = isset($memberPenalties[$id]) ? $memberPenalties[$id] : [];
            
            // Summe der Strafen für dieses Mitglied berechnen
            $totalPenalty = 0;
            foreach ($member['penalties'] as $penalty) {
                if ($penalty['istanzahl']) {
                    // Bei Anzahl (istanzahl = 1) den Strafentyp-Preis mit der Anzahl multiplizieren
                    $anzahl = intval($penalty['betrag']);
                    
                    // Wenn es ein Strafentyp ist, verwenden wir den Preis aus der Join-Abfrage
                    if ($penalty['idstrafentyp'] > 0 && isset($penalty['preis'])) {
                        // Preis direkt aus dem Join verwenden
                        $preis = floatval($penalty['preis']);
                        $strafBetrag = $anzahl * $preis;
                        $totalPenalty += $strafBetrag;
                    } else {
                        // Bei individuellen Strafen mit istanzahl ohne Preis
                        // Nur als Information anzeigen, kein Betrag zur Summe hinzufügen
                    }
                } else {
                    // Bei Geldbeträgen (istanzahl = 0) direkt den Betrag addieren
                    $strafBetrag = floatval($penalty['betrag']);
                    $totalPenalty += $strafBetrag;
                }
            }
            $member['total_penalty'] = $totalPenalty;
            
            if (isset($attendance[$id]) && $attendance[$id] == 1) {
                $presentMembers[] = $member;
            } else {
                $absentMembers[] = $member;
            }
        }
        
        // Durchschnittsbetrag basierend auf den Gesamtstrafen der anwesenden Mitglieder berechnen
        $totalSumForAvg = 0;
        $countForAvg = count($presentMembers);
        
        // Summe aller Strafen der anwesenden Mitglieder berechnen
        foreach ($presentMembers as $member) {
            $totalSumForAvg += $member['total_penalty'];
        }
        
        // Durchschnittsbetrag berechnen
        $averagePenalty = ($countForAvg > 0) ? $totalSumForAvg / $countForAvg : 0;
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
    <style>
        .members-list {
            width: 100%;
            border-collapse: collapse;
        }
        .members-list th, 
        .members-list td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        .members-list th {
            background-color: #2c2c2c;
            color: #1e8ad6;
        }
        .penalty-item {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header> 
    <h1>Kegelstrafen</h1>
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
    </div>
    <?php if ($selectedEvent): ?>
        <?php if ($countForAvg > 0): ?>
            <div class="info-box" style="margin: 10px auto 20px; background-color: #333; padding: 10px; border-radius: 5px; max-width: 600px; text-align: center;">
                <p style="margin: 0;">
                    Durchschnittsberechnung basiert auf <?= $countForAvg ?> anwesenden Mitgliedern, Gesamtsumme: <?= number_format($totalSumForAvg, 2, ',', '.') ?>€, 
                    <strong>Durchschnitt: <?= number_format($averagePenalty, 2, ',', '.') ?>€</strong> 
                    <br>
                    <small style="color: #888;">Basierend auf Gesamtstrafen aller anwesenden Mitglieder</small>
                </p>
            </div>
        <?php endif; ?>
        <div class="penalties-container">
            <h2>Anwesende Mitglieder</h2>
            <table class="members-list">
                <tr>
                    <th>Vorname</th>
                    <th>Strafen</th>
                    <th>Summe</th>
                </tr>
                <?php foreach ($presentMembers as $member): ?>
                    <tr>
                        <td><?= htmlspecialchars($member['vorname']) ?></td>
                        <td>
                            <?php if (!empty($member['penalties'])): ?>
                                <ul style="list-style:none; margin:0; padding:0;">
                                <?php foreach ($member['penalties'] as $penalty): ?>
                                    <li>
                                        <?php if ($penalty['idstrafentyp'] > 0): ?>
                                            <?= htmlspecialchars($penalty['strafentyp_bezeichnung']) ?>
                                            (<?= $penalty['istanzahl'] ? $penalty['betrag'] . 'x' : number_format($penalty['betrag'], 2, ',', '.') . '€' ?>)
                                        <?php else: ?>
                                            <?= htmlspecialchars($penalty['grund']) ?>
                                            (<?= $penalty['istanzahl'] ? $penalty['betrag'] . 'x' : number_format($penalty['betrag'], 2, ',', '.') . '€' ?>)
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                Keine Strafen
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold; color: #ff9900;">
                            <?= number_format($member['total_penalty'], 2, ',', '.') ?> €
                            <?php 
                            // Debug-Info: Zeige die Berechnung für jede Strafe
                            if (!empty($member['penalties'])):
                            ?>
                            <small style="display:block; color:#6a6a6a; font-weight:normal; margin-top:5px;">
                                <?php foreach ($member['penalties'] as $penalty): ?>
                                    <?php if ($penalty['istanzahl'] && $penalty['idstrafentyp'] > 0): ?>
                                        <?= $penalty['betrag'] ?> x <?= isset($penalty['preis']) ? $penalty['preis'] : '?' ?>€
                                        = <?= $penalty['betrag'] * (isset($penalty['preis']) ? $penalty['preis'] : 0) ?>€<br>
                                    <?php elseif (!$penalty['istanzahl']): ?>
                                        <?= number_format($penalty['betrag'], 2, ',', '.') ?>€<br>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($presentMembers)): ?>
                    <tr><td colspan="3">Keine anwesenden Mitglieder.</td></tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="penalties-container" style="margin-top:40px;">
            <h2>Nicht anwesende Mitglieder (müssen Durchschnitt bezahlen)</h2>
            <table class="members-list">
                <tr><th>Vorname</th><th>Durchschnittsstrafe</th></tr>
                <?php foreach ($absentMembers as $member): ?>
                    <tr>
                        <td><?= htmlspecialchars($member['vorname']) ?></td>
                        <td style="font-weight: bold; color: #ff4400;">
                            <?= number_format($averagePenalty, 2, ',', '.') ?> €
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($absentMembers)): ?>
                    <tr><td colspan="2">Keine nicht anwesenden Mitglieder.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    <?php endif; ?>
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