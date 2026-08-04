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

    // Δημιουργία πίνακα ονομάτων αυτόματα αν δεν υπάρχει
    $pdo->exec("CREATE TABLE IF NOT EXISTS pgg_player_names (
        uuid VARCHAR(64) PRIMARY KEY,
        display_name VARCHAR(255)
    )");

    // Τραβάμε τα Top 25 playtime από το ajLeaderboards
    $stmt = $pdo->prepare("SELECT id as uuid, value FROM ajlb_extras WHERE placeholder = ? ORDER BY CAST(value AS UNSIGNED) DESC LIMIT 25");
    $stmt->execute(['statistic_play_one_minute']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedData = [];
    
    foreach ($results as $row) {
        $ticks = intval($row['value']);
        $hours = round($ticks / 72000, 2);
        $uuid = $row['uuid'];

        // Default fallback όνομα
        $username = "Player_" . substr($uuid, 0, 5);

        // Ψάχνουμε αν υπάρχει αποθηκευμένο όνομα/RP όνομα στον πίνακα
        $stmtName = $pdo->prepare("SELECT display_name FROM pgg_player_names WHERE uuid = ?");
        $stmtName.execute([$uuid]);
        $nameRow = $stmtName->fetch(PDO::FETCH_ASSOC);

        if ($nameRow && !empty($nameRow['display_name'])) {
            $username = $nameRow['display_name'];
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
