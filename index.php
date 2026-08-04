<?php
// Επιτρέπουμε στο site σου να διαβάζει τα δεδομένα (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Στοιχεία σύνδεσης MySQL (PebbleHost)
$host = 'uk02-sql.pebblehost.com';
$db   = 'customer_1492946_ajLeaderboards';
$user = 'customer_1492946_ajLeaderboards';
$pass = 'CdVtoTa=rof231sr8fa1PGy^';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Παίρνουμε τους παίκτες από τον πίνακα ajlb_extras για το placeholder των kills
    $stmt = $pdo->prepare("SELECT id as uuid, value FROM ajlb_extras WHERE placeholder = ? ORDER BY CAST(value AS UNSIGNED) DESC LIMIT 5");
    $stmt->execute(['statistic_player_kills']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedData = [];
    foreach ($results as `row`) {
        $formattedData[] = [
            'uuid' => $row['uuid'],
            'value' => $row['value']
        ];
    }

    echo json_encode($formattedData);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>
