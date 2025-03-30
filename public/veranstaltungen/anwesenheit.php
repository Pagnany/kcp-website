<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Pagnany\Kcp\DatabaseConnect;
use Pagnany\Kcp\Auth\Auth;

$isLoggedIn = Auth::isLoggedIn();
$error = null;
$success = null;
$events = [];
$members = [];
$attendance = [];
$selectedEvent = null;

if (!$isLoggedIn) {
    header('Location: /public/login');
    exit;
}

try {
    $db = new DatabaseConnect();
    $conn = $db->getConnection();

    // Fetch events
    $stmt = $conn->query("SELECT idveranstaltungen, titel, datumvon FROM veranstaltung ORDER BY datumvon DESC");
    $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Fetch members
    $stmt = $conn->query("SELECT idmitglieder, nickname, vorname, nachname FROM mitglieder WHERE aktiv = true ORDER BY nachname, vorname");
    $members = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eventId = $_POST['event_id'] ?? null;
        $attendanceData = $_POST['attendance'] ?? [];

        if ($eventId) {
            // Start transaction
            $conn->beginTransaction();

            try {
                // Delete existing attendance records for this event
                $stmt = $conn->prepare("DELETE FROM anwesenheit WHERE id_veranstaltung = :event_id");
                $stmt->execute([':event_id' => $eventId]);

                // Insert new attendance records
                $stmt = $conn->prepare("INSERT INTO anwesenheit (id_veranstaltung, id_mitglied, anwesend) VALUES (:event_id, :member_id, :attendance)");
                
                foreach ($attendanceData as $memberId => $status) {
                    if ($status !== '') { // Only insert if a status was selected
                        $stmt->execute([
                            ':event_id' => $eventId,
                            ':member_id' => $memberId,
                            ':attendance' => $status === 'true' ? 1 : 0
                        ]);
                    }
                }

                $conn->commit();
                $success = "Anwesenheit wurde erfolgreich gespeichert!";
                header("Location: veranstaltungen");
                exit;
            } catch (\Exception $e) {
                $conn->rollBack();
                $error = "Fehler beim Speichern der Anwesenheit: " . $e->getMessage();
            }
        }
    }

    // If an event is selected, fetch its attendance data
    if (isset($_GET['event_id'])) {
        $selectedEvent = $_GET['event_id'];
        $stmt = $conn->prepare("SELECT id_mitglied, anwesend FROM anwesenheit WHERE id_veranstaltung = :event_id");
        $stmt->execute([':event_id' => $selectedEvent]);
        $attendance = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
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
    <title>KCP - Anwesenheit</title>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header> 

    <h2>Anwesenheit</h2>
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
                <button type="submit" class="submit-button">Veranstaltung auswählen</button>
            </div>
        </form>

        <?php if ($selectedEvent): ?>
            <form method="POST" class="event-form">
                <input type="hidden" name="event_id" value="<?= $selectedEvent ?>">
                <div class="attendance-list">
                    <?php foreach ($members as $member): ?>
                        <div class="attendance-item">
                            <label class="member-name">
                                <?= htmlspecialchars($member['nickname']) ?> 
                                (<?= htmlspecialchars($member['vorname']) ?> 
                                <?= htmlspecialchars($member['nachname']) ?>)
                            </label>
                            <div class="attendance-options">
                                <label>
                                    <input type="radio" name="attendance[<?= $member['idmitglieder'] ?>]" value="true"
                                        <?= isset($attendance[$member['idmitglieder']]) && $attendance[$member['idmitglieder']] == 1 ? 'checked' : '' ?>>
                                    Anwesend
                                </label>
                                <label>
                                    <input type="radio" name="attendance[<?= $member['idmitglieder'] ?>]" value="false"
                                        <?= isset($attendance[$member['idmitglieder']]) && $attendance[$member['idmitglieder']] == 0 ? 'checked' : '' ?>>
                                    Nicht anwesend
                                </label>
                                <label>
                                    <input type="radio" name="attendance[<?= $member['idmitglieder'] ?>]" value=""
                                        <?= !isset($attendance[$member['idmitglieder']]) ? 'checked' : '' ?>>
                                    Nicht gesetzt
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-group">
                    <button type="submit" class="submit-button">Anwesenheit speichern</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <style>
        .attendance-list {
            margin-top: 20px;
        }
        .attendance-item {
            background-color: #3a3a3a;
            border-radius: 6px;
            padding: 15px 20px;
            margin-bottom: 15px;
        }
        .member-name {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #1e8ad6;
        }
        .attendance-options {
            display: flex;
            gap: 20px;
        }
        .attendance-options label {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #222;
            color: #fafafa;
            font-size: 16px;
        }
        select:focus {
            outline: none;
            border-color: #1e8ad6;
            box-shadow: 0 0 0 2px rgba(30, 138, 214, 0.2);
        }
    </style>
</body>
</html> 