<?php
$host = 'uk02-sql.pebblehost.com';
$db   = 'customer_1492946_ajLeaderboards';
$user = 'customer_1492946_ajLeaderboards';
$pass = 'CdVtoTa=rof231sr8fa1PGy^';

if (isset($_GET['username']) && isset($_GET['uuid'])) {
    $username = $_GET['username'];
    $uuid = $_GET['uuid'];

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        
        // Δημιουργία πίνακα αποθήκευσης ονομάτων για cracked παίκτες
        $pdo->exec("CREATE TABLE IF NOT EXISTS pgg_player_names (uuid VARCHAR(64) PRIMARY KEY, username VARCHAR(36))");
        
        // Αποθήκευση ή ενημέρωση του ονόματος
        $stmt = $pdo->prepare("INSERT INTO pgg_player_names (uuid, username) VALUES (?, ?) ON DUPLICATE KEY UPDATE username = ?");
        $stmt->execute([$uuid, $username, $username]);
        
        echo "OK";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
