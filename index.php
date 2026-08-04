<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$host = 'uk02-sql.pebblehost.com';
$db   = 'customer_1492946_ajLeaderboards';
$user = 'customer_1492946_ajLeaderboards';
$pass = 'CdVtoTa=rof231sr8fa1PGy^';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Φέρνει τους Top 25 παίκτες
    $stmt = $pdo->prepare("SELECT id as uuid, value FROM ajlb_extras WHERE placeholder = ? ORDER BY CAST(value AS UNSIGNED) DESC LIMIT 25");
    $stmt->execute(['statistic_play_one_minute']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedData = [];
    foreach ($results as $row) {
        $ticks = intval($row['value']);
        $hours = round($ticks / 72000, 2);

        $uuid = $row['uuid'];

        // --- ΛΙΣΤΑ ΠΑΙΚΤΩΝ (UUID => USERNAME) ---
        // Πρόσθεσε εδώ όποιον άλλο παίκτη θέλεις στο μέλλον
        $knownPlayers = [
            'b0635010-209e-37d0-9573-39a7e4cf18f3' => 'ThFos',
            '530fa97a-357f-3c19-94d3-0c5c65c18fe8' => 'Ονομα_Παίκτη' // <-- Βάλε εδώ το κανονικό του username
        ];

        // Αν υπάρχει στη λίστα παίρνει το όνομα, αλλιώς βάζει το προσωρινό
        $username = isset($knownPlayers[$uuid]) ? $knownPlayers[$uuid] : "Player_" . substr($uuid, 0, 5);

        $formattedData[] = [
            'uuid' => $uuid,
            'username' => $username,
            'playtime' => $hours . ' hrs'
        ];
    }

    echo json_encode($formattedData);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>
