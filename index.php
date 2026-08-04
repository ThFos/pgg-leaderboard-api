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

    $stmt = $pdo->prepare("SELECT id as uuid, value FROM ajlb_extras WHERE placeholder = ? ORDER BY CAST(value AS UNSIGNED) DESC LIMIT 5");
    $stmt->execute(['statistic_play_one_minute']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedData = [];
    foreach ($results as $row) {
        $ticks = intval($row['value']);
        $hours = round($ticks / 72000, 2);

        // Μετατροπή UUID σε Username μέσω Mojang API
        $uuidClean = str_replace('-', '', $row['uuid']);
        $username = $row['uuid']; 
        $mojangUrl = "https://sessionserver.mojang.com/session/minecraft/profile/" . $uuidClean;
        
        $opts = ['http' => ['header' => "User-Agent: PHP\r\n"]];
        $context = stream_context_create($opts);
        $response = @file_get_contents($mojangUrl, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['name'])) {
                $username = $data['name'];
            }
        }

        $formattedData[] = [
            'username' => $username,
            'playtime' => $hours . ' hrs'
        ];
    }

    echo json_encode($formattedData);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>
