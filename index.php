<?php
// Επιτρέπουμε στο GitHub Pages να διαβάζει τα δεδομένα (CORS)
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

    // Παίρνουμε τους 5 πρώτους παίκτες από τον πίνακα του ajLeaderboards
    $stmt = $pdo->query("SELECT player as username, value FROM ajlb_statistic_hours_played ORDER BY CAST(value AS UNSIGNED) DESC LIMIT 5");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedData = [];
    foreach ($results as $row) {
        $formattedData[] = [
            'username' => $row['username'],
            'playtime' => $row['value'] . 'h'
        ];
    }

    echo json_encode($formattedData);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>
