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

    // 1. Παίρνουμε τους Top 25 παίκτες με βάση το playtime
    $sql = "SELECT e.id as uuid, e.value 
            FROM ajlb_extras e 
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

        // Αρχική τιμή username (fallback)
        $username = "Player_" . substr($uuid, 0, 5);

        // 2. Ψάχνουμε στη βάση αν το Skript έχει αποθηκεύσει όνομα χαρακτήρα για αυτό το UUID
        // (Το Skript αποθηκεύει τις μεταβλητές στον πίνακα skript_variables ή αντίστοιχο ανάλογα την έκδοση, 
        // αλλά ο ασφαλέστερος τρόπος είναι να τσεκάρουμε τον πίνακα παικτών του ajLeaderboards ή τα aliases)
        
        // Εναλλακτικά, τραβάμε το όνομα από τον πίνακα ajlb_players του ajLeaderboards
        $stmtPlayer = $pdo->prepare("SELECT name FROM ajlb_players WHERE uuid = ?");
        $stmtPlayer->execute([$uuid]);
        $playerRow = $stmtPlayer->fetch(PDO::FETCH_ASSOC);
        
        if ($playerRow && !empty($playerRow['name'])) {
            $username = $playerRow['name'];
        }

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
