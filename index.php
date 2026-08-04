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
```[cite: 6, 9]

---

### 2. Ο ενημερωμένος κώδικας για το `index.html` (Frontend)
Στην HTML/JS πλευρά, το μόνο που χρειάζεται πλέον είναι να περνάμε απ' ευθείας το `player.headUrl` στο `src` της εικόνας:

```html
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    <title>Leaderboard - PGG Legacy</title>
    
    <link rel="icon" type="image/png" href="/media/favicon.png">
    
    <!-- Libraries CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=Space+Grotesk:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="/style.css">

    <style>
        body {
            background-color: #121212;
            color: #fff;
            font-family: 'Space Grotesk', sans-serif;
            overflow-x: hidden;
            margin: 0;
        }

        /* --- NAVBAR FIXES --- */
        .desktop_navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 40px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(18, 18, 18, 0.9);
            backdrop-filter: blur(10px);
            box-sizing: border-box;
            z-index: 1000;
        }

        .desktop_navbar_logo_img {
            height: 40px;
            display: block;
        }

        .desktop_navbar_links_wrapper {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .desktop_navbar_link {
            color: #ccc;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 12px;
            transition: color 0.3s ease;
            font-size: 0.95rem;
        }

        .desktop_navbar_link:hover {
            color: #fff;
        }

        .desktop_navbar_link2_div {
            background: #5865F2;
            border-radius: 8px;
            padding: 8px 18px;
            white-space: nowrap;
        }

        .desktop_navbar_link2 {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }

        .mobile_navbar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #121212;
            padding: 15px 20px;
            box-sizing: border-box;
            z-index: 1000;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .mobile_navbar_logo_img {
            height: 35px;
            display: block;
        }

        .icon {
            cursor: pointer;
            font-size: 1.5rem;
            color: #fff;
        }

        #mobile_navbar_links {
            display: none;
            flex-direction: column;
            width: 100%;
            position: absolute;
            top: 100%;
            left: 0;
            background: #121212;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 10px 0;
        }

        #mobile_navbar_links a {
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
        }

        @media only screen and (max-width: 1100px) {
            .desktop_navbar {
                display: none;
            }
            .mobile_navbar {
                display: flex;
            }
        }

        /* --- LEADERBOARD UI --- */
        .leaderboard-container {
            max-width: 900px;
            margin: 140px auto 60px;
            padding: 0 20px;
        }

        .leaderboard-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .leaderboard-subtitle {
            text-align: center;
            color: #aaa;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .leaderboard-tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #aaa;
            padding: 10px 24px;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .tab-btn.active {
            background: #5865F2;
            border-color: #5865F2;
            color: #fff;
            box-shadow: 0 0 15px rgba(88, 101, 242, 0.4);
        }

        .leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .list-item {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 15px 25px;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .list-item:hover {
            transform: translateX(10px);
            background: rgba(255, 255, 255, 0.06);
        }

        .list-item.rank-1 {
            border-color: rgba(255, 215, 0, 0.5);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.1);
        }
        .list-item.rank-2 {
            border-color: rgba(192, 192, 192, 0.4);
        }
        .list-item.rank-3 {
            border-color: rgba(205, 127, 50, 0.4);
        }

        .item-rank {
            font-size: 1.8rem;
            font-weight: 800;
            width: 60px;
        }
        
        .item-head {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            margin-right: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.5);
            image-rendering: pixelated;
        }

        .item-username {
            flex-grow: 1;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .item-time {
            font-size: 1.2rem;
            color: #5865F2;
            font-weight: bold;
            text-align: right;
        }

        @media only screen and (max-width: 600px) {
            .list-item {
                flex-wrap: wrap;
                padding: 15px;
            }
            .item-time {
                width: 100%;
                text-align: left;
                margin-top: 10px;
                padding-left: 70px;
                font-size: 1rem;
            }
            .leaderboard-container {
                margin-top: 100px;
            }
        }
    </style>
</head>
<body>

    <!-- ANIMATIONS ΠΟΝΤΙΚΙΟΥ -->
    <div class="custom-cursor" id="cursor"></div>
    <div class="cursor-glow" id="cursorGlow"></div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- NAVBAR (Desktop) -->
    <nav class="desktop_navbar">
      <a href="/"><img class="desktop_navbar_logo_img" src="/media/logo_small.svg" alt="logo"></a>
      <div class="desktop_navbar_links_wrapper">
          <a href="/#rules" class="desktop_navbar_link">Rules</a>
          <a href="/#discord" class="desktop_navbar_link">Community</a>
          <a href="/#about" class="desktop_navbar_link">About Us</a>
          <a href="/map" class="desktop_navbar_link">Server Map</a>
          <a href="/leaderboard" class="desktop_navbar_link" style="color: #5865F2; font-weight: bold;">Leaderboard</a>
          <a href="/police" class="desktop_navbar_link" style="color: #ffcc00; font-weight: bold;">Police Applications</a>
      </div>
      <div class="desktop_navbar_link2_div">
          <a href="/#join" class="desktop_navbar_link2">Join Us!</a>
      </div>
    </nav>

    <!-- NAVBAR (Mobile) -->
    <div class="mobile_navbar">
      <a href="/" class="mobile_navbar_logo_img_a">
        <img class="mobile_navbar_logo_img" src="/media/logo_small.svg" alt="logo">
      </a>
      <div class="icon" onclick="toggleMobileMenu()">
          <i class="fa fa-bars" id="menuIcon"></i>
      </div>
      
      <div id="mobile_navbar_links">
          <a class="mobile_navbar_link" onclick="toggleMobileMenu()" href="/#rules">Rules</a>
          <a class="mobile_navbar_link" onclick="toggleMobileMenu()" href="/#discord">Community</a>
          <a class="mobile_navbar_link" onclick="toggleMobileMenu()" href="/#about">About Us</a>
          <a class="mobile_navbar_link" href="/map">Server Map</a>
          <a class="mobile_navbar_link" href="/leaderboard" style="color: #5865F2; font-weight: bold;">Leaderboard</a>
          <a class="mobile_navbar_link" href="/police" style="color: #ffcc00; font-weight: bold;">Police Apps</a>
          <a class="mobile_navbar_link" onclick="toggleMobileMenu()" href="/#join">Join Us</a>
      </div>
    </div>

    <!-- MAIN LEADERBOARD CONTENT -->
    <div class="leaderboard-container">
        <h1 class="leaderboard-title" data-aos="fade-down">Top 25 Playtime</h1>
        <p class="leaderboard-subtitle" data-aos="fade-up" data-aos-delay="100">Οι 25 παίκτες με τις περισσότερες ώρες στον PGG Legacy Server</p>

        <!-- TABS ΕΝΑΛΛΑΓΗΣ (Weekly Πρώτο) -->
        <div class="leaderboard-tabs" data-aos="fade-up" data-aos-delay="150">
            <button class="tab-btn active" id="btn-weekly" onclick="switchPeriod('weekly')">Weekly</button>
            <button class="tab-btn" id="btn-all" onclick="switchPeriod('all')">All-Time</button>
        </div>

        <!-- CONTAINER ΛΙΣΤΑΣ -->
        <div class="leaderboard-list" id="leaderboard-list" data-aos="fade-up" data-aos-delay="200">
            <!-- Τα δεδομένα φορτώνονται δυναμικά εδώ -->
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="copyright_div" style="margin-top: 60px; text-align: center; padding-bottom: 20px;">
      <a href="#" class="copyright" style="color: #888; text-decoration: none;">© 2026 PGG Legacy - All rights reserved.</a>
    </footer>

    <!-- SCRIPTS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
      AOS.init({ duration: 800, once: true, disable: window.innerWidth < 768 });

      function toggleMobileMenu() {
        const menu = document.getElementById("mobile_navbar_links");
        const icon = document.getElementById("menuIcon");
        if (menu.style.display === "flex") {
            menu.style.display = "none";
            icon.className = "fa fa-bars";
        } else {
            menu.style.display = "flex";
            icon.className = "fa fa-times";
        }
      }

      function switchPeriod(period) {
          document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
          if (period === 'all') {
              document.getElementById('btn-all').classList.add('active');
          } else {
              document.getElementById('btn-weekly').classList.add('active');
          }
          fetchLeaderboard(period);
      }

      async function fetchLeaderboard(period = 'weekly') {
          const listContainer = document.getElementById('leaderboard-list');
          listContainer.innerHTML = '<p style="text-align:center; color:#aaa; padding: 20px;">Φόρτωση δεδομένων...</p>';
          
          try {
              const endpoint = period === 'weekly' 
                  ? 'https://pgg-leaderboard-api.onrender.com?period=weekly' 
                  : 'https://pgg-leaderboard-api.onrender.com';

              const response = await fetch(endpoint); 
              const players = await response.json();

              listContainer.innerHTML = ''; 

              if (!players || players.length === 0 || players.error) {
                  const message = period === 'weekly' 
                      ? 'Δεν υπάρχουν διαθέσιμα δεδομένα για αυτήν την εβδομάδα.' 
                      : 'Δεν βρέθηκαν δεδομένα ακόμα.';
                  listContainer.innerHTML = `<p style="text-align:center; color:#aaa; padding: 20px;">${message}</p>`;
                  return;
              }

              players.slice(0, 25).forEach((player, index) => {
                  const rank = index + 1;
                  let rankColor = '#888';
                  let rankClass = '';

                  if (rank === 1) { rankColor = '#ffd700'; rankClass = 'rank-1'; }
                  else if (rank === 2) { rankColor = '#c0c0c0'; rankClass = 'rank-2'; }
                  else if (rank === 3) { rankColor = '#cd7f32'; rankClass = 'rank-3'; }

                  const itemHTML = `
                      <div class="list-item ${rankClass}">
                          <div class="item-rank" style="color: ${rankColor};">#${rank}</div>
                          <img class="item-head" src="${player.headUrl}" alt="${player.username}'s Head">
                          <div class="item-username">${player.username}</div>
                          <div class="item-time">${player.playtime}</div>
                      </div>
                  `;
                  
                  listContainer.insertAdjacentHTML('beforeend', itemHTML);
              });
          } catch (error) {
              console.error('Σφάλμα κατά τη φόρτωση:', error);
              listContainer.innerHTML = '<p style="text-align:center; color:#ff4d4d; padding: 20px;">Δεν ήταν δυνατή η σύνδεση με τη βάση δεδομένων.</p>';
          }
      }

      // Φόρτωση του weekly εξ αρχής
      fetchLeaderboard('weekly');

      if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        const cursor = document.getElementById('cursor');
        const glow = document.getElementById('cursorGlow');
        document.addEventListener('mousemove', (e) => {
          cursor.style.left = e.clientX + 'px';
          cursor.style.top = e.clientY + 'px';
          glow.style.left = e.clientX + 'px';
          glow.style.top = e.clientY + 'px';
        });
      }

      document.addEventListener('contextmenu', e => e.preventDefault());
      document.addEventListener('keydown', e => {
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key.toUpperCase())) || (e.ctrlKey && ['U','S','C'].includes(e.key.toUpperCase()))) {
          e.preventDefault();
        }
      });
      document.addEventListener('dragstart', e => e.preventDefault());
    </script>
</body>
</html>
```[cite: 8]
