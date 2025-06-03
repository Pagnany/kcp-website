<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Pagnany\Kcp\DatabaseConnect;

$events = [];

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


        // Anwesenheit in anwesend/nicht anwesend aufteilen
        $anwesende = [];
        $nicht_anwesende = [];
        if (!empty($anwesenheit)) {
            foreach ($anwesenheit as $mitglied) {
                if (!empty($mitglied['anwesend'])) {
                    $anwesende[] = $mitglied;
                } else {
                    $nicht_anwesende[] = $mitglied;
                }
            }
        }

        // alle Strafen für Mitglieder
        $stmt = $conn->prepare("
            SELECT 
                mitglieder.idmitglieder,
                strafen.idstrafentyp,
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
                strafentyp.preis,
                strafen.istanzahl,
                strafen.in_durchschnitt
            FROM strafen 
            LEFT JOIN strafentyp ON strafen.idstrafentyp = strafentyp.id 
            WHERE strafen.idveranstaltung = :event_id
            ORDER BY strafen.in_durchschnitt DESC, strafen.idstrafentyp DESC
        ");
        $stmt->execute([':event_id' => $selectedEvent]);
        $strafen = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Strafen in in_durchschnitt und nicht in_durchschnitt aufteilen
        $strafen_in_durchschnitt = [];
        $strafen_nicht_in_durchschnitt = [];
        
        foreach ($strafen as $strafe) {
            if (!empty($strafe['in_durchschnitt'])) {
                $strafen_in_durchschnitt[] = $strafe;
            } else {
                $strafen_nicht_in_durchschnitt[] = $strafe;
            }
        }
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
        .kegelstrafen-tabelle th, .kegelstrafen-tabelle td {
            border: 1px solid #888;
            text-align: center;
            padding: 6px 8px;
        }
        .kegelstrafen-tabelle th {
            background:rgb(110, 110, 110);
        }
    </style>
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
    
    <?php if (!empty($anwesenheit) && !empty($strafen)): ?>
        <?php if (!empty($anwesende) && !empty($strafen)): ?>
            <h2>Anwesende Mitglieder</h2>
            <table class="kegelstrafen-tabelle">
                <thead>
                    <tr>
                        <th>Mitglied</th>
                        <?php if (!empty($strafen_in_durchschnitt)): ?>
                            <?php foreach ($strafen_in_durchschnitt as $strafe): ?>
                                <th>
                                    <?php if (isset($strafe['idstrafentyp']) && $strafe['idstrafentyp'] == 0): ?>
                                        <?= htmlspecialchars($strafe['grund']) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($strafe['bezeichnung']) ?>
                                        <?php if (!empty($strafe['istanzahl']) && $strafe['istanzahl']): ?>
                                            <br><span style="font-weight:normal;font-size:0.9em;">
                                                (<?= number_format($strafe['preis'], 2, ',', '.') ?> €)
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                            <th>Zwischensumme</th>
                        <?php endif; ?>
                        
                        <?php if (!empty($strafen_nicht_in_durchschnitt)): ?>
                            <?php foreach ($strafen_nicht_in_durchschnitt as $strafe): ?>
                                <th>
                                    <?php if (isset($strafe['idstrafentyp']) && $strafe['idstrafentyp'] == 0): ?>
                                        <?= htmlspecialchars($strafe['grund']) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($strafe['bezeichnung']) ?>
                                        <?php if (!empty($strafe['istanzahl']) && $strafe['istanzahl']): ?>
                                            <br><span style="font-weight:normal;font-size:0.9em;">
                                                (<?= number_format($strafe['preis'], 2, ',', '.') ?> €)
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <th>Gesamtsumme</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $gesamt_summe = 0; $zwischensumme_gesamt = 0; $anzahl_anwesende = count($anwesende); ?>
                    <?php foreach ($anwesende as $mitglied): ?>
                        <tr>
                            <td><?= htmlspecialchars($mitglied['vorname']) ?></td>
                            <?php 
                                $mitglied_durchschnitt_summe = 0;
                                $mitglied_normal_summe = 0;
                            ?>
                            
                            <?php // Strafen mit in_durchschnitt anzeigen ?>
                            <?php if (!empty($strafen_in_durchschnitt)): ?>
                                <?php foreach ($strafen_in_durchschnitt as $strafe): ?>
                                    <?php
                                        $betrag = '';
                                        $anzeige = '';
                                        $istanzahl = false;
                                        if (!empty($strafen_mitglieder)) {
                                            foreach ($strafen_mitglieder as $sm) {
                                                $anzeige = '';
                                                $istanzahl = false;
                                                if ($sm['idmitglieder'] == $mitglied['idmitglieder'] && $sm['idstrafentyp'] == $strafe['idstrafentyp']) {
                                                    if ($sm['idstrafentyp'] == 0) {
                                                        if ($sm['grund'] === $strafe['grund']) {
                                                            if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                                $anzeige = $sm['betrag'];
                                                                $betrag = $sm['betrag'] * $sm['preis'];
                                                                $istanzahl = true;
                                                            } else {
                                                                $anzeige = $sm['betrag'];
                                                                $betrag = $sm['betrag'];
                                                            }
                                                            break;
                                                        }
                                                    } else {
                                                        if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                            $anzeige = $sm['betrag'];
                                                            $betrag = $sm['betrag'] * $sm['preis'];
                                                            $istanzahl = true;
                                                        } else {
                                                            $anzeige = $sm['betrag'];
                                                            $betrag = $sm['betrag'];
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                        if ($betrag !== '' && is_numeric($betrag)) {
                                            $mitglied_durchschnitt_summe += $betrag;
                                        }
                                    ?>
                                    <td><?= $anzeige !== '' ? htmlspecialchars($anzeige) . (!$istanzahl ? ' €' : '') : '-' ?></td>
                                <?php endforeach; ?>
                                <td><strong><?= number_format($mitglied_durchschnitt_summe, 2, ',', '.') ?> €</strong></td>
                            <?php endif; ?>
                            
                            <?php // Strafen ohne in_durchschnitt anzeigen ?>
                            <?php if (!empty($strafen_nicht_in_durchschnitt)): ?>
                                <?php foreach ($strafen_nicht_in_durchschnitt as $strafe): ?>
                                    <?php
                                        $betrag = '';
                                        $anzeige = '';
                                        $istanzahl = false;
                                        if (!empty($strafen_mitglieder)) {
                                            foreach ($strafen_mitglieder as $sm) {
                                                $anzeige = '';
                                                $istanzahl = false;
                                                if ($sm['idmitglieder'] == $mitglied['idmitglieder'] && $sm['idstrafentyp'] == $strafe['idstrafentyp']) {
                                                    if ($sm['idstrafentyp'] == 0) {
                                                        if ($sm['grund'] === $strafe['grund']) {
                                                            if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                                $anzeige = $sm['betrag'];
                                                                $betrag = $sm['betrag'] * $sm['preis'];
                                                                $istanzahl = true;
                                                            } else {
                                                                $anzeige = $sm['betrag'];
                                                                $betrag = $sm['betrag'];
                                                            }
                                                            break;
                                                        }
                                                    } else {
                                                        if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                            $anzeige = $sm['betrag'];
                                                            $betrag = $sm['betrag'] * $sm['preis'];
                                                            $istanzahl = true;
                                                        } else {
                                                            $anzeige = $sm['betrag'];
                                                            $betrag = $sm['betrag'];
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                        if ($betrag !== '' && is_numeric($betrag)) {
                                            $mitglied_normal_summe += $betrag;
                                        }
                                    ?>
                                    <td><?= $anzeige !== '' ? htmlspecialchars($anzeige) . (!$istanzahl ? ' €' : '') : '-' ?></td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php $mitglied_gesamt = $mitglied_durchschnitt_summe + $mitglied_normal_summe; ?>
                            <td><strong><?= number_format($mitglied_gesamt, 2, ',', '.') ?> €</strong></td>
                        </tr>
                        <?php $gesamt_summe += $mitglied_gesamt; ?>
                    <?php endforeach; ?>
                      <tr style="background:rgb(110, 110, 110);font-weight:bold;">
                        <td>Summe aller Anwesenden</td>
                        <?php 
                        if (!empty($strafen_in_durchschnitt)) {
                            for ($i = 0; $i < count($strafen_in_durchschnitt); $i++) {
                                echo '<td></td>';
                            }
                            // Berechnung der gesamten Zwischensumme
                            foreach ($anwesende as $mitglied) {
                                $mitglied_summe = 0;
                                foreach ($strafen_mitglieder as $sm) {
                                    if ($sm['idmitglieder'] == $mitglied['idmitglieder'] && !empty($sm['in_durchschnitt'])) {
                                        $betrag = 0;
                                        if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                            $betrag = $sm['betrag'] * $sm['preis'];
                                        } else {
                                            $betrag = $sm['betrag'];
                                        }
                                        if (is_numeric($betrag)) {
                                            $mitglied_summe += $betrag;
                                        }
                                    }
                                }
                                $zwischensumme_gesamt += $mitglied_summe;
                            }
                            echo '<td><strong>' . number_format($zwischensumme_gesamt, 2, ',', '.') . " €</strong></td>";
                        }
                        if (!empty($strafen_nicht_in_durchschnitt)) {
                            for ($i = 0; $i < count($strafen_nicht_in_durchschnitt); $i++) {
                                echo '<td></td>';
                            }
                        }
                        echo '<td>' . number_format($gesamt_summe, 2, ',', '.') . " €</td>";
                        // Durchschnitt berechnen
                        $durchschnitt_zwischensumme = $anzahl_anwesende > 0 ? $zwischensumme_gesamt / $anzahl_anwesende : 0;
                        ?>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>        <?php
        // Für nicht anwesende Mitglieder: Nur Strafen-Spalten anzeigen, die mindestens einmal vorkommen
        $strafen_in_durchschnitt_nicht_anwesende = [];
        $strafen_nicht_in_durchschnitt_nicht_anwesende = [];
        
        if (!empty($nicht_anwesende)) {
            // Durchschnitt-Strafen für nicht anwesende
            if (!empty($strafen_in_durchschnitt)) {
                foreach ($strafen_in_durchschnitt as $strafe) {
                    foreach ($nicht_anwesende as $mitglied) {
                        if (!empty($strafen_mitglieder)) {
                            foreach ($strafen_mitglieder as $sm) {
                                if (
                                    $sm['idmitglieder'] == $mitglied['idmitglieder'] &&
                                    $sm['idstrafentyp'] == $strafe['idstrafentyp'] &&
                                    (
                                        $sm['idstrafentyp'] != 0 ||
                                        ($sm['idstrafentyp'] == 0 && $sm['grund'] === $strafe['grund'])
                                    )
                                ) {
                                    $strafen_in_durchschnitt_nicht_anwesende[] = $strafe;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
            
            // Normale Strafen für nicht anwesende
            if (!empty($strafen_nicht_in_durchschnitt)) {
                foreach ($strafen_nicht_in_durchschnitt as $strafe) {
                    foreach ($nicht_anwesende as $mitglied) {
                        if (!empty($strafen_mitglieder)) {
                            foreach ($strafen_mitglieder as $sm) {
                                if (
                                    $sm['idmitglieder'] == $mitglied['idmitglieder'] &&
                                    $sm['idstrafentyp'] == $strafe['idstrafentyp'] &&
                                    (
                                        $sm['idstrafentyp'] != 0 ||
                                        ($sm['idstrafentyp'] == 0 && $sm['grund'] === $strafe['grund'])
                                    )
                                ) {
                                    $strafen_nicht_in_durchschnitt_nicht_anwesende[] = $strafe;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }
        ?>        <?php if (!empty($nicht_anwesende)) { ?>
            <h2>Nicht anwesende Mitglieder</h2>
            <table class="kegelstrafen-tabelle">
                <thead>
                    <tr>
                        <th>Mitglied</th>
                        <?php if (!empty($strafen_in_durchschnitt_nicht_anwesende)) {
                            foreach ($strafen_in_durchschnitt_nicht_anwesende as $strafe) { ?>
                                <th>
                                    <?php if (isset($strafe['idstrafentyp']) && $strafe['idstrafentyp'] == 0) {
                                        echo htmlspecialchars($strafe['grund']);
                                } else {
                                    echo htmlspecialchars($strafe['bezeichnung']);
                                    if (!empty($strafe['istanzahl']) && $strafe['istanzahl']) {
                                        echo '<br><span style="font-weight:normal;font-size:0.9em;">(' . number_format($strafe['preis'], 2, ',', '.') . ' €)</span>';
                                    }
                                } ?>
                                </th>
                            <?php }
                            echo '<th style="background:#8a8a8a;">Zwischensumme</th>';
                        }
                        if (!empty($strafen_nicht_in_durchschnitt_nicht_anwesende)) {
                            foreach ($strafen_nicht_in_durchschnitt_nicht_anwesende as $strafe) { ?>
                                <th>
                                    <?php if (isset($strafe['idstrafentyp']) && $strafe['idstrafentyp'] == 0) {
                                        echo htmlspecialchars($strafe['grund']);
                                } else {
                                    echo htmlspecialchars($strafe['bezeichnung']);
                                    if (!empty($strafe['istanzahl']) && $strafe['istanzahl']) {
                                        echo '<br><span style="font-weight:normal;font-size:0.9em;">(' . number_format($strafe['preis'], 2, ',', '.') . ' €)</span>';
                                    }
                                } ?>
                                </th>
                            <?php }
                        }
                        ?>
                        <th>Durchschnitt (Anwesende)</th>
                        <th>Gesamtsumme</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nicht_anwesende as $mitglied) { ?>
                        <tr>
                            <td><?= htmlspecialchars($mitglied['vorname']) ?></td>
                            <?php 
                                $mitglied_durchschnitt_summe = 0;
                                $mitglied_normal_summe = 0;
                            ?>
                            <?php if (!empty($strafen_in_durchschnitt_nicht_anwesende)) {
                                foreach ($strafen_in_durchschnitt_nicht_anwesende as $strafe) {
                                    $betrag = '';
                                    $anzeige = '';
                                    $istanzahl = false;
                                    if (!empty($strafen_mitglieder)) {
                                        foreach ($strafen_mitglieder as $sm) {
                                            $anzeige = '';
                                            $istanzahl = false;
                                            if ($sm['idmitglieder'] == $mitglied['idmitglieder'] && $sm['idstrafentyp'] == $strafe['idstrafentyp']) {
                                                if ($sm['idstrafentyp'] == 0) {
                                                    if ($sm['grund'] === $strafe['grund']) {
                                                        if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                            $anzeige = $sm['betrag'];
                                                            $betrag = $sm['betrag'] * $sm['preis'];
                                                            $istanzahl = true;
                                                        } else {
                                                            $anzeige = $sm['betrag'];
                                                            $betrag = $sm['betrag'];
                                                        }
                                                        break;
                                                    }
                                                } else {
                                                    if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                        $anzeige = $sm['betrag'];
                                                        $betrag = $sm['betrag'] * $sm['preis'];
                                                        $istanzahl = true;
                                                    } else {
                                                        $anzeige = $sm['betrag'];
                                                        $betrag = $sm['betrag'];
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if ($betrag !== '' && is_numeric($betrag)) {
                                        $mitglied_durchschnitt_summe += $betrag;
                                    }
                                    echo '<td>' . ($anzeige !== '' ? htmlspecialchars($anzeige) . (!$istanzahl ? ' €' : '') : '-') . '</td>';
                                }
                                echo '<td style="background:#e0e0e0;"><strong>' . number_format($mitglied_durchschnitt_summe, 2, ',', '.') . ' €</strong></td>';
                            }
                            if (!empty($strafen_nicht_in_durchschnitt_nicht_anwesende)) {
                                foreach ($strafen_nicht_in_durchschnitt_nicht_anwesende as $strafe) {
                                    $betrag = '';
                                    $anzeige = '';
                                    $istanzahl = false;
                                    if (!empty($strafen_mitglieder)) {
                                        foreach ($strafen_mitglieder as $sm) {
                                            $anzeige = '';
                                            $istanzahl = false;
                                            if ($sm['idmitglieder'] == $mitglied['idmitglieder'] && $sm['idstrafentyp'] == $strafe['idstrafentyp']) {
                                                if ($sm['idstrafentyp'] == 0) {
                                                    if ($sm['grund'] === $strafe['grund']) {
                                                        if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                            $anzeige = $sm['betrag'];
                                                            $betrag = $sm['betrag'] * $sm['preis'];
                                                            $istanzahl = true;
                                                        } else {
                                                            $anzeige = $sm['betrag'];
                                                            $betrag = $sm['betrag'];
                                                        }
                                                        break;
                                                    }
                                                } else {
                                                    if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                        $anzeige = $sm['betrag'];
                                                        $betrag = $sm['betrag'] * $sm['preis'];
                                                        $istanzahl = true;
                                                    } else {
                                                        $anzeige = $sm['betrag'];
                                                        $betrag = $sm['betrag'];
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if ($betrag !== '' && is_numeric($betrag)) {
                                        $mitglied_normal_summe += $betrag;
                                    }
                                    echo '<td>' . ($anzeige !== '' ? htmlspecialchars($anzeige) . (!$istanzahl ? ' €' : '') : '-') . '</td>';
                                }
                            }
                            $mitglied_gesamt = $mitglied_durchschnitt_summe + $mitglied_normal_summe + $durchschnitt_zwischensumme;
                            echo '<td>' . number_format($durchschnitt_zwischensumme, 2, ',', '.') . ' €</td>';
                            echo '<td><strong>' . number_format($mitglied_gesamt, 2, ',', '.') . ' €</strong></td>';
                            ?>
                        </tr>
                    <?php } ?>
                    <?php 
                    // Gesamtsumme für nicht anwesende Mitglieder berechnen
                    $gesamt_summe_nicht_anwesende = 0;
                    foreach ($nicht_anwesende as $mitglied) {
                        $mitglied_durchschnitt_summe = 0;
                        $mitglied_normal_summe = 0;
                        if (!empty($strafen_in_durchschnitt_nicht_anwesende)) {
                            foreach ($strafen_in_durchschnitt_nicht_anwesende as $strafe) {
                                $betrag = '';
                                if (!empty($strafen_mitglieder)) {
                                    foreach ($strafen_mitglieder as $sm) {
                                        if ($sm['idmitglieder'] == $mitglied['idmitglieder'] && $sm['idstrafentyp'] == $strafe['idstrafentyp']) {
                                            if ($sm['idstrafentyp'] == 0) {
                                                if ($sm['grund'] === $strafe['grund']) {
                                                    if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                        $betrag = $sm['betrag'] * $sm['preis'];
                                                    } else {
                                                        $betrag = $sm['betrag'];
                                                    }
                                                    break;
                                                }
                                            } else {
                                                if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                    $betrag = $sm['betrag'] * $sm['preis'];
                                                } else {
                                                    $betrag = $sm['betrag'];
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                                if ($betrag !== '' && is_numeric($betrag)) {
                                    $mitglied_durchschnitt_summe += $betrag;
                                }
                            }
                        }
                        if (!empty($strafen_nicht_in_durchschnitt_nicht_anwesende)) {
                            foreach ($strafen_nicht_in_durchschnitt_nicht_anwesende as $strafe) {
                                $betrag = '';
                                if (!empty($strafen_mitglieder)) {
                                    foreach ($strafen_mitglieder as $sm) {
                                        if ($sm['idmitglieder'] == $mitglied['idmitglieder'] && $sm['idstrafentyp'] == $strafe['idstrafentyp']) {
                                            if ($sm['idstrafentyp'] == 0) {
                                                if ($sm['grund'] === $strafe['grund']) {
                                                    if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                        $betrag = $sm['betrag'] * $sm['preis'];
                                                    } else {
                                                        $betrag = $sm['betrag'];
                                                    }
                                                    break;
                                                }
                                            } else {
                                                if (!empty($sm['istanzahl']) && $sm['istanzahl']) {
                                                    $betrag = $sm['betrag'] * $sm['preis'];
                                                } else {
                                                    $betrag = $sm['betrag'];
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                                if ($betrag !== '' && is_numeric($betrag)) {
                                    $mitglied_normal_summe += $betrag;
                                }
                            }
                        }
                        $mitglied_gesamt = $mitglied_durchschnitt_summe + $mitglied_normal_summe + $durchschnitt_zwischensumme;
                        $gesamt_summe_nicht_anwesende += $mitglied_gesamt;
                    }
                    // Spalten zählen
                    $spalten = 1;
                    if (!empty($strafen_in_durchschnitt_nicht_anwesende)) {
                        $spalten += count($strafen_in_durchschnitt_nicht_anwesende) + 1; // +1 für Zwischensumme
                    }
                    if (!empty($strafen_nicht_in_durchschnitt_nicht_anwesende)) {
                        $spalten += count($strafen_nicht_in_durchschnitt_nicht_anwesende);
                    }
                    // +1 für Durchschnitt (Anwesende)
                    $spalten += 1;
                    ?>
                    <tr style="background:rgb(110, 110, 110);font-weight:bold;">
                        <td colspan="<?= $spalten ?>" style="text-align:right;">Summe aller Nicht-Anwesenden</td>
                        <td><strong><?= number_format($gesamt_summe_nicht_anwesende, 2, ',', '.') ?> €</strong></td>
                    </tr>
                </tbody>
            </table>
        <?php } ?>
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