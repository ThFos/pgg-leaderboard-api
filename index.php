<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

$host = 'uk02-sql.pebblehost.com';
$db   = 'customer_1492946_ajLeaderboards';
$user = 'customer_1492946_ajLeaderboards';
$pass = 'CdVtoTa=rof231sr8fa1PGy^';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT e.id as uuid, e.value, a.realname as username 
            FROM ajlb_extras e 
            LEFT JOIN authme a ON e.id = a.uuid 
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
        
        $username = !empty($row['username']) ? $row['username'] : "Player_" . substr($row['uuid'], 0, 5);

        // Διόρθωση: Σωστή δομή πίνακα για καθαρό JSON
        $formattedData[] = [
            'uuid' => $row['uuid'],
            'username' => $username,
            'playtime' => $hours . ' hrs'
        ];
    }

    echo json_encode($formattedData);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
