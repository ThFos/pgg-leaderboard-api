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

    // Χρησιμοποιούμε αποκλειστικά το skinrestorer_player_history που υπάρχει στη βάση σου
    $srJoin = "LEFT JOIN (
                SELECT h1.uuid, h1.skin_identifier, h1.skin_type 
                FROM skinrestorer_player_history h1
                INNER JOIN (
                    SELECT uuid, MAX(timestamp) as max_time 
                    FROM skinrestorer_player_history 
                    GROUP BY uuid
                ) h2 ON h1.uuid = h2.uuid AND h1.timestamp = h2.max_time
            ) sr ON t.id = sr.uuid";

    if ($period === 'weekly') {
        $sql = "SELECT t.id as uuid, t.weekly_delta as value, a.realname as username, sr.skin_identifier, sr.skin_type 
                FROM ajlb_statistic_play_one_minute t 
                LEFT JOIN authme a ON t.id = a.uuid 
                {$srJoin}
                ORDER BY CAST(t.weekly_delta AS UNSIGNED) DESC 
                LIMIT 25";
    } else {
        $sql = "SELECT t.id as uuid, t.value, a.realname as username, sr.skin_identifier, sr.skin_type 
                FROM ajlb_statistic_play_one_minute t 
                LEFT JOIN authme a ON t.id = a.uuid 
                {$srJoin}
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
        
        $username = !empty($row['username']) ? trim($row['username']) : "Player_" . substr($row['uuid'], 0, 5);
        $skinType = strtoupper(trim($row['skin_type'] ?? ''));
        $skinId   = trim($row['skin_identifier'] ?? '');

        // Fallback head URL
        $headUrl = "https://mc-heads.net/avatar/" . urlencode($username) . "/50";

        if (!empty($skinId)) {
            // 1. Έλεγχος αν είναι link
            if ($skinType === 'URL' || filter_var($skinId, FILTER_VALIDATE_URL) || strpos($skinId, 'http') === 0) {
                
                // --- Διόρθωση Mineskin API μέσω cURL ---
                if (strpos($skinId, 'minesk.in/') !== false) {
                    $mineskinId = basename(parse_url($skinId, PHP_URL_PATH));
                    $ch = curl_init("https://api.mineskin.org/get/uuid/" . $mineskinId);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'PGG-Legacy-Leaderboard');
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Αγνόησε προβλήματα SSL
                    $apiJson = curl_exec($ch);
                    curl_close($ch);
                    
                    if ($apiJson) {
                        $data = json_decode($apiJson, true);
                        if (!empty($data['data']['texture']['url'])) {
                            $skinId = $data['data']['texture']['url']; // Το καθαρό Minecraft Texture URL
                        }
                    }
                }
                
                // --- Λήψη & Κοπή Εικόνας μέσω cURL ---
                if ($hasGd) {
                    $ch = curl_init($skinId);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $skinContent = curl_exec($ch);
                    curl_close($ch);
                    
                    if ($skinContent) {
                        $skinImg = @imagecreatefromstring($skinContent);
                        if ($skinImg !== false) {
                            $headImg = imagecreatetruecolor(50, 50);
                            imagesavealpha($headImg, true);
                            $transparent = imagecolorallocatealpha($headImg, 0, 0, 0, 127);
                            imagefill($headImg, 0, 0, $transparent);
                            
                            // Κοπή προσώπου και καπέλου
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
            } else {
                // Αν είναι απλό όνομα skin (π.χ. Notch)
                $headUrl = "https://mc-heads.net/avatar/" . urlencode($skinId) . "/50";
            }
        }

        $formattedData[] = [
            'uuid'     => $row['uuid'],
            'username' => $username,
            'playtime' => $hours . ' hrs',
            'headUrl'  => $headUrl
        ];
    }

    echo json_encode($formattedData);

} catch (PDOException $e) {
    // Εμφάνιση σφάλματος για ευκολότερο debugging
    echo json_encode(["error" => $e->getMessage()]);
}
?>
