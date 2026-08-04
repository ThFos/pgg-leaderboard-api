<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

$host = 'uk02-sql.pebblehost.com';
$db   = 'customer_1492946_ajLeaderboards';
$user = 'customer_1492946_ajLeaderboards';
$pass = 'CdVtoTa=rof231sr8fa1PGy^';

// Ελέγχουμε αν ζήτησε weekly ή all-time (default είναι all)
$period = isset($_GET['period']) ? $_GET['period'] : 'all';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Επιλέγουμε ποιο placeholder ή query θα τρέξει ανάλογα με την επιλογή
    if ($period === 'weekly') {
        // Αν η ajLeaderboards έχει ξεχωριστό placeholder για εβδομάδα (ή βάζεις το δικό σου SQL query)
        $placeholder = 'statistic_play_one_minute_weekly'; 
    } else {
        // All-time placeholder
        $placeholder = 'statistic_play_one_minute';
    }

    $sql = "SELECT e.id as uuid, e.value, a.realname as username 
            FROM ajlb_extras e 
            LEFT JOIN authme a ON e.id = a.uuid 
            WHERE e.placeholder = ? 
            ORDER BY CAST(e.value AS UNSIGNED) DESC 
            LIMIT 25";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$placeholder]);
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
    echo json_encode(["error" => $e->getMessage()]);
}
?>
