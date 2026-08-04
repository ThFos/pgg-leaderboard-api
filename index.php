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

    // 1. Παίρνουμε τα Top 25 playtime από το ajLeaderboards
    $stmt = $pdo->prepare("SELECT id as uuid, value FROM ajlb_extras WHERE placeholder = ? ORDER BY CAST(value AS UNSIGNED) DESC LIMIT 25");
    $stmt->execute(['statistic_play_one_minute']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Φορτώνουμε όλα τα αποθηκευμένα cracked ονόματα στη μνήμη για γρήγορη αντιστοίχιση
    $nameMap = [];
    $nameStmt = $pdo->query("SELECT uuid, username FROM pgg_player_names");
    while ($row = $nameStmt->fetch(PDO::FETCH_ASSOC)) {
        $nameMap[$row['uuid']] = $row['username'];
    }

    $formattedData = [];

    foreach ($results as $row) {
        $ticks = intval($row['value']);
        $hours = round($ticks / 72000, 2);
        $uuid = $row['uuid'];

        // Ανάγνωση του cracked ονόματος από τη βάση μας (με fallback αν λείπει)
        $username = isset($nameMap[$uuid]) ? $nameMap[$uuid] : "Player_" . substr($uuid, 0, 5);

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
