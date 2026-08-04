<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

// Στοιχεία Βάσης
$host = 'uk02-sql.pebblehost.com';
$db   = 'customer_1492946_ajLeaderboards';
$user = 'customer_1492946_ajLeaderboards';
$pass = 'CdVtoTa=rof231sr8fa1PGy^';

$period = isset($_GET['period']) ? $_GET['period'] : 'all';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // JOIN για το ιστορικό ΚΑΙ τον πίνακα των URL skins για έξτρα ταχύτητα
    $srJoin = "LEFT JOIN (
                SELECT h1.uuid, h1.skin_identifier, h1.skin_type 
                FROM skinrestorer_player_history h1
                INNER JOIN (
                    SELECT uuid, MAX(timestamp) as max_time 
                    FROM skinrestorer_player_history 
                    GROUP BY uuid
                ) h2 ON h1.uuid = h2.uuid AND h1.timestamp = h2.max_time
            ) sr ON t.id = sr.uuid
            LEFT JOIN skinrestorer_url_skins urls ON sr.skin_identifier = urls.url";

    if ($period === 'weekly') {
        $sql = "SELECT t.id as uuid, t.weekly_delta as value, a.realname as username, sr.skin_identifier, sr.skin_type, urls.value as skin_base64 
                FROM ajlb_statistic_play_one_minute t 
                LEFT JOIN authme a ON t.id = a.uuid 
                {$srJoin}
                ORDER BY CAST(t.weekly_delta AS UNSIGNED) DESC 
                LIMIT 25";
    } else {
        $sql = "SELECT t.id as uuid, t.value, a.realname as username, sr.skin_identifier, sr.skin_type, urls.value as skin_base64 
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
        $skinBase64 = trim($row['skin_base64'] ?? '');

        // Fallback head URL (αν δεν έχει skin)
        $headUrl = "https://mc-heads.net/avatar/" . urlencode($username) . "/50";

        if (!empty($skinId)) {
            // Αν είναι Custom URL Skin
            if ($skinType === 'URL' || filter_var($skinId, FILTER_VALIDATE_URL) || strpos($skinId, 'http') === 0) {
                
                $textureUrl = '';
                
                // 1. Δοκιμάζουμε να πάρουμε το URL απευθείας από τη βάση σου (πολύ πιο γρήγορο)
                if (!empty($skinBase64)) {
                    $decoded = base64_decode($skinBase64);
                    if ($decoded) {
                        $json = json_decode($decoded, true);
                        if (isset($json['textures']['SKIN']['url'])) {
                            $textureUrl = $json['textures']['SKIN']['url'];
                        }
                    }
                }

                // 2. Fallback στο API του Mineskin αν δεν βρέθηκε στη βάση σου
                if (empty($textureUrl) && strpos($skinId, 'minesk.in/') !== false) {
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
                }
                
                // 3. Αν δεν το βρήκαμε με κανέναν τρόπο, κρατάμε το αρχικό link (μήπως είναι ήδη .png)
                if (empty($textureUrl)) {
                    $textureUrl = $skinId;
                }

                // --- Λήψη & Κοπή Εικόνας μέσω cURL & GD Library ---
                if ($hasGd && !empty($textureUrl)) {
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
                            
                            // Κοπή προσώπου και καπέλου/μαλλιών (overlay)
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
                // Αν είναι απλό όνομα premium skin (π.χ. Notch)
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
    echo json_encode(["error" => $e->getMessage()]);
}
?>
