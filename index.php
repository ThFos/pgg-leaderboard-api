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

    // 1. Τραβάμε τα δεδομένα από το ajlb_extras
    $stmt = $pdo->prepare("SELECT id as uuid, value FROM ajlb_extras WHERE placeholder = ? ORDER BY CAST(value AS UNSIGNED) DESC LIMIT 25");
    $stmt->execute(['statistic_play_one_minute']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedData = [];
    
    foreach ($results as $row) {
        $ticks = intval($row['value']);
        $hours = round($ticks / 72000, 2);
        $uuid = $row['uuid'];

        // Προεπιλεγμένο όνομα ασφαλείας
        $username = "Player_" . substr($uuid, 0, 5);

        // 2. Προσπάθεια ανάγνωσης ονόματος από τον πίνακα ajlb_players (αν υπάρχει)
        try {
            $stmtPlayer = $pdo->prepare("SELECT name FROM ajlb_players WHERE uuid = ?");
            $stmtPlayer->execute([$uuid]);
            $playerRow = $stmtPlayer->fetch(PDO::FETCH_ASSOC);
            
            if ($playerRow && !empty($playerRow['name'])) {
                $username = $playerRow['name'];
            }
        } catch (Exception $ex) {
            // Αν δεν υπάρχει ο πίνακας ajlb_players, αγνοείται σιωπηρά και μένει το fallback
        }

        $formattedData[] = [
            'uuid' => $uuid,
            'username' => $username,
            'playtime' => $hours . ' hrs'
        ];
    }

    echo json_encode($formattedData);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
