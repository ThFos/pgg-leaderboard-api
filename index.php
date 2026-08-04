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

    // Κάνουμε JOIN με τον πίνακα ajlb_players για να παίρνει αυτόματα το όνομα του κάθε παίκτη από τη βάση
    $sql = "SELECT e.id as uuid, e.value, p.name as username 
            FROM ajlb_extras e 
            LEFT JOIN ajlb_players p ON e.id = p.uuid 
            WHERE e.placeholder = ? 
            ORDER BY CAST(e.value AS UNSIGNED) DESC 
            LIMIT 25";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['statistic_play_one_minute']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedData = [];
    foreach ($results as $row) {
        $ticks = intval($row['value']);
        $hours = round($ticks / 72000, 2);

        $uuid = $row['uuid'];
        
        // Αν η βάση έχει αποθηκευμένο το όνομα, το χρησιμοποιεί. Αλλιώς βάζει fallback.
        $username = (!empty($row['username'])) ? $row['username'] : "Player_" . substr($uuid, 0, 5);

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
