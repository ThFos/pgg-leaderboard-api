<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

$host = 'uk02-sql.pebblehost.com';
$db   = 'customer_1492946_ajLeaderboards';
$user = 'customer_1492946_ajLeaderboards';
$pass = 'CdVtoTa=rof231sr8fa1PGy^';

$period = isset($_GET['period']) ? $_GET['period'] : 'all';

// Ορίζουμε το placeholder ανάλογα με την περίοδο
$placeholder = ($period === 'weekly') ? 'statistic_play_one_minute_weekly' : 'statistic_play_one_minute';

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
    $stmt->execute([$placeholder]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Αν είναι κενό το weekly, μπορούμε να ελέγξουμε τι placeholders υπάρχουν στη βάση
    if (empty($results) && $period === 'weekly') {
        $checkStmt = $pdo->query("SELECT DISTINCT placeholder FROM ajlb_extras LIMIT 10");
        $availablePlaceholders = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Αν θέλετε να δείτε ποια ονόματα υπάρχουν διαθέσιμα στη βάση σας, 
        // μπορείτε προσωρινά να επιστρέψετε αυτά τα δεδομένα:
        // echo json_encode(["error" => "Placeholder not found", "available" => $availablePlaceholders]);
        // exit;
    }

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
