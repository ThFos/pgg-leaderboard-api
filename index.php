<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

$host = 'uk02-sql.pebblehost.com';
$db   = 'customer_1492946_ajLeaderboards';
$user = 'customer_1492946_ajLeaderboards';
$pass = 'CdVtoTa=rof231sr8fa1PGy^';

$period = isset($_GET['period']) ? $_GET['period'] : 'all';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($period === 'weekly') {
        // Αν το ajLeaderboards δημιουργεί ξεχωριστό πίνακα για weekly (π.χ. ajlb_statistic_play_one_minute_weekly)
        // Ανάλογα με την ονομασία του πίνακα, αλλάζεις το παρακάτω:
        $sql = "SELECT t.id as uuid, t.value, a.realname as username 
                FROM ajlb_statistic_play_one_minute_weekly t 
                LEFT JOIN authme a ON t.id = a.uuid 
                ORDER BY CAST(t.value AS UNSIGNED) DESC 
                LIMIT 25";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        // All-Time από τον κύριο πίνακα
        $sql = "SELECT t.id as uuid, t.value, a.realname as username 
                FROM ajlb_statistic_play_one_minute t 
                LEFT JOIN authme a ON t.id = a.uuid 
                ORDER BY CAST(t.value AS UNSIGNED) DESC 
                LIMIT 25";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $formattedData = [];

    foreach ($results as $row) {
        $ticks = intval($row['value']);
        $hours = round($ticks / 72000, 2);
        
        $username = !empty($row['username']) ? $row['username'] : "Player_" . substr($row['uuid'], 0, 5);

        $formattedData[] = [
            'uuid' => $row['uuid'],
            'username' => $username,
            'playtime' => $hours . ' hrs'
        ];
    }

    echo json_encode($formattedData);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>
