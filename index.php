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
        $sql = "SELECT t.id as uuid, t.weekly_delta as value, a.realname as username, sr.skin_identifier, sr.skin_type 
                FROM ajlb_statistic_play_one_minute t 
                LEFT JOIN authme a ON t.id = a.uuid 
                LEFT JOIN skinrestorer_players sr ON t.id = sr.uuid 
                ORDER BY CAST(t.weekly_delta AS UNSIGNED) DESC 
                LIMIT 25";
    } else {
        $sql = "SELECT t.id as uuid, t.value, a.realname as username, sr.skin_identifier, sr.skin_type 
                FROM ajlb_statistic_play_one_minute t 
                LEFT JOIN authme a ON t.id = a.uuid 
                LEFT JOIN skinrestorer_players sr ON t.id = sr.uuid 
                ORDER BY CAST(t.value AS UNSIGNED) DESC 
                LIMIT 25";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $formattedData = [];

    $hasGd = extension_loaded('gd');

    foreach ($results as $row) {
        $ticks = intval($row['value']);
        $hours = round($ticks / 72000, 2);
        
        $username = !empty($row['username']) ? $row['username'] : "Player_" . substr($row['uuid'], 0, 5);
        $headUrl = "https://mc-heads.net/avatar/" . $row['uuid'] . "/50";

        if ($hasGd && $row['skin_type'] === 'URL' && !empty($row['skin_identifier'])) {
            $skinContent = @file_get_contents($row['skin_identifier']);
            if ($skinContent !== false) {
                $skinImg = @imagecreatefromstring($skinContent);
                if ($skinImg !== false) {
                    $headImg = imagecreatetruecolor(50, 50);
                    imagesavealpha($headImg, true);
                    $transparent = imagecolorallocatealpha($headImg, 0, 0, 0, 127);
                    imagefill($headImg, 0, 0, $transparent);
                    
                    imagecopyresampled($headImg, $skinImg, 0, 0, 8, 8, 50, 50, 8, 8);
                    imagecopyresampled($headImg, $skinImg, 0, 0, 40, 8, 50, 50, 8, 8);
                    
                    ob_start();
                    imagepng($headImg);
                    $headUrl = 'data:image/png;base64,' . base64_encode(ob_get_clean());
                    
                    imagedestroy($skinImg);
                    imagedestroy($headImg);
                }
            }
        }

        $formattedData[] = [
            'uuid' => $row['uuid'],
            'username' => $username,
            'playtime' => $hours . ' hrs',
            'headUrl' => $headUrl
        ];
    }

    echo json_encode($formattedData);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>
