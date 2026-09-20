<?php
    require_once __DIR__ . '/../../../../config/config.php';
    require_once __DIR__ . '/../../../../config/database.php';
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([232]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sessId = substr(md5('edge_sess_' . 232), 0, 26);
    $sessFile = 'C:/xampp/tmp/sess_' . $sessId;
    $sessData = [
        'user_id' => (int) $u['id'],
        'username' => $u['username'],
        'user_role' => $u['role_key'],
        'user_role_key' => $u['role_key'],
        'login_at' => time(),
        'last_activity' => time(),
        'csrf_token' => bin2hex(random_bytes(16)),
    ];
    $raw = '';
    foreach ($sessData as $k => $v) { $raw .= $k . '|' . serialize($v); }
    file_put_contents($sessFile, $raw);
    
    setcookie('SMS2SESSID', $sessId, ['expires' => time() + 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    header('Location: /Clone/sms2-capstone/modules/faculty/views/registrar/faculty-clearance.php');
    exit;
