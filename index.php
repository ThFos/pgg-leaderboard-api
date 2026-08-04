<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

$host = 'uk02-sql.pebblehost.com';
$db   = 'customer_1492946_ajLeaderboards'; // Ή η βάση δεδομένων όπου αποθηκεύονται οι πίνακες του AuthMe
$user = 'customer_1492946_ajLeaderboards';
$pass = 'CdVtoTa=rof231sr8fa1PGy^';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Συνδυάζουμε το playtime από το ajLeaderboards με το realname από το AuthMe
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
        
        // Αν για κάποιο λόγο δεν βρεθεί στον πίνακα του AuthMe, βάζουμε μια εναλλακτική ονομασία
        $username = !empty($row['username']) ? $row['username'] : "Player_" . substr($row['uuid'], 0, 5);

        $formattedData[] = [
            'uuid' => $row['uuid'],
            'username' => $username,
            $formattedData['playtime'] = $hours . ' hrs'
        ];
    }

    echo json_encode($formattedData);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
