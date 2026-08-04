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

    // 1. Διαβάζουμε πρώτα από τον πίνακα skinrestorer_players (ενεργό skin)
    // 2. Αν δεν υπάρχει, διαβάζουμε από το skinrestorer_player_history
    // 3. Συνδέουμε με το skinrestorer_url_skins για να πάρουμε το έτοιμο texture value
    $srJoin = "LEFT JOIN skinrestorer_players sp ON REPLACE(t.id, '-', '') = REPLACE(sp.uuid, '-', '')
               LEFT JOIN (
                   SELECT h1.uuid, h1.skin_identifier, h1.skin_type 
                   FROM skinrestorer_player_history h1
                   INNER JOIN (
                       SELECT uuid, MAX(timestamp) as max_time 
                       FROM skinrestorer_player_history 
                       GROUP BY uuid
                   ) h2 ON h1.uuid = h2.uuid AND h1.timestamp = h2.max_time
               ) sph ON REPLACE(t.id, '-', '') = REPLACE(sph.uuid, '-', '')
               LEFT JOIN skinrestorer_url_skins urls ON COALESCE(sp.skin_identifier, sph.skin_identifier) = urls.url";

    if ($period === 'weekly') {
        $sql = "SELECT t.id as uuid, t.weekly_delta as value, a.realname as username, 
                       COALESCE(sp.skin_identifier, sph.skin_identifier) as skin_identifier, 
                       COALESCE(sp.skin_type, sph.skin_type) as skin_type, 
                       urls.value as skin_base64 
                FROM ajlb_statistic_play_one_minute t 
                LEFT JOIN authme a ON REPLACE(t.id, '-', '') = REPLACE(a.uuid, '-', '')
                {$srJoin}
                ORDER BY CAST(t.weekly_delta AS UNSIGNED) DESC 
                LIMIT 25";
    } else {
        $sql = "SELECT t.id as uuid, t.value, a.realname as username, 
                       COALESCE(sp.skin_identifier, sph.skin_identifier) as skin_identifier, 
                       COALESCE(sp.skin_type, sph.skin_type) as skin_type, 
                       urls.value as skin_base64 
                FROM ajlb_statistic_play_one_minute t 
                LEFT JOIN authme a ON REPLACE(t.id, '-', '') = REPLACE(a.uuid, '-', '')
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
        $skinBase64 = trim($row['skin_base64'] ?? '');

        // Default Fallback Head
        $headUrl = "https://mc-heads.net/avatar/" . urlencode($username) . "/50";

        $textureUrl = '';

        // Αποκωδικοποίηση του Base64 JSON που αποθηκεύει το SkinRestorer
        if (!empty($skinBase64)) {
            $decoded = base64_decode($skinBase64);
            if ($decoded) {
                $json = json_decode($decoded, true);
                if (isset($json['textures']['SKIN']['url'])) {
                    $textureUrl = $json['textures']['SKIN']['url'];
                }
            }
        }

        // Αν είναι URL skin αλλά δεν βρέθηκε στη βάση, προσπαθούμε μέσω Mineskin API
        if (empty($textureUrl) && ($skinType === 'URL' || filter_var($skinId, FILTER_VALIDATE_URL) || strpos($skinId, 'http') === 0)) {
            if (strpos($skinId, 'minesk.in/') !== false) {
                $mineskinId = basename(parse_url($skinId, PHP_URL_PATH));
                $ch = curl_init("https://api.mineskin.org/get/uuid/" . $mineskinId);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'PGG-Legacy-Leaderboard');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $apiJson = curl_exec($ch);
                curl_close($ch);
                
                if ($apiJson) {
                    $data = json_decode($apiJson, true);
                    if (!empty($data['data']['texture']['url'])) {
                        $textureUrl = $data['data']['texture']['url'];
                    }
                }
            } else {
                $textureUrl = $skinId;
            }
        }

        // Αν βρήκαμε το texture URL, κόβουμε το κεφάλι με την GD library
        if (!empty($textureUrl)) {
            if ($hasGd) {
                $ch = curl_init($textureUrl);
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
                        
                        // Κοπή προσώπου & καπέλου
                        imagecopyresampled($headImg, $skinImg, 0, 0, 8, 8, 50, 50, 8, 8);
                        imagecopyresampled($headImg, $skinImg, 0, 0, 40, 8, 50, 50, 8, 8);
                        
                        ob_start();
                        imagepng($headImg);
                        $headUrl = 'data:image/png;base64,' . base64_encode(ob_get_clean());
                        
                        imagedestroy($skinImg);
                        imagedestroy($headImg);
                    }
                }
            } else {
                // Αν δεν υπάρχει GD στο Render, παίρνουμε το κεφάλι απευθείας μέσω του Hash της Mojang
                $textureHash = basename(parse_url($textureUrl, PHP_URL_PATH));
                $headUrl = "https://mc-heads.net/avatar/" . $textureHash . "/50";
            }
        } else if (!empty($skinId) && $skinType !== 'URL') {
            // Αν είναι όνομα παίκτη (π.χ. Notch)
            $headUrl = "https://mc-heads.net/avatar/" . urlencode($skinId) . "/50";
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
    echo json_encode(["error" => $e->getMessage()]);
}
?>
