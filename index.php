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

    $stmt = $pdo->prepare("SELECT id as uuid, value FROM ajlb_extras WHERE placeholder = ? ORDER BY CAST(value AS UNSIGNED) DESC LIMIT 5");
    $stmt->execute(['statistic_play_one_minute']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedData = [];
    foreach ($results as $row) {
        $ticks = intval($row['value']);
        $hours = round($ticks / 72000, 2);

        $uuid = $row['uuid'];

        // Λίστα με τα ονόματα των παίκτών βάσει του UUID τους
        $knownPlayers = [
            'b0635010-209e-37d0-9573-39a7e4cf18f3' => 'ThFos'
            // Αν θες να προσθέσεις κι άλλον παίκτη στο μέλλον, τον βάζεις έτσι:
            // 'άλλο-uuid-εδώ' => 'ΌνομαΠαίκτη'
        ];

        // Αν υπάρχει στη λίστα παίρνει το σωστό όνομα, αλλιώς βάζει προσωρινό
        $username = isset($knownPlayers[$uuid]) ? $knownPlayers[$uuid] : "Player";

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
