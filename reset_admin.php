<?php
require_once 'includes/config.inc.php';

// Veritabanı bağlantısı
$dsn = "mysql:host=" . RL_DBHOST . ";port=" . RL_DBPORT . ";dbname=" . RL_DBNAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, RL_DBUSER, RL_DBPASS, $options);

    // Kullanıcı adı: almmunich, Şifre: 532 (MD5)
    $username = 'almmunich';
    $password = '532';
    $passwordHash = md5($password);

    // Kullanıcı var mı kontrol et
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . RL_DBPREFIX . "admins WHERE User = ?");
    $stmt->execute([$username]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        // Güncelle
        $updateStmt = $pdo->prepare("UPDATE " . RL_DBPREFIX . "admins SET Pass = ? WHERE User = ?");
        $updateStmt->execute([$passwordHash, $username]);
        echo "✅ Başarılı: '$username' kullanıcısının şifresi güncellendi.<br>";
    } else {
        // Yoksa oluştur (Opsiyonel ama güvenli)
        echo "⚠️ Uyarı: '$username' kullanıcısı bulunamadı. Lütfen veritabanındaki admin kullanıcı adını kontrol edin.<br>";

        // Mevcut adminleri listele
        $listStmt = $pdo->query("SELECT User FROM " . RL_DBPREFIX . "admins");
        echo "Mevcut Admin Kullanıcıları: " . implode(", ", $listStmt->fetchAll(PDO::FETCH_COLUMN));
    }
} catch (\PDOException $e) {
    echo "❌ Hata: " . $e->getMessage();
}
