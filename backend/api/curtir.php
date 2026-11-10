<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!userIsAuthenticated()) {
    jsonResponse(401, ['message' => 'Login necessário para curtir']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$idPublicacao = (int) ($data['publicacao_id'] ?? 0);
if ($idPublicacao <= 0) {
    jsonResponse(400, ['message' => 'Publicação inválida']);
}

$pdo = db();
$userId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT id FROM curtidas WHERE id_usuario = :uid AND id_publicacao = :pid LIMIT 1');
$stmt->execute(['uid' => $userId, 'pid' => $idPublicacao]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare('DELETE FROM curtidas WHERE id_usuario = :uid AND id_publicacao = :pid')->execute([
        'uid' => $userId,
        'pid' => $idPublicacao,
    ]);
    $liked = false;
} else {
    $pdo->prepare('INSERT INTO curtidas (id_usuario, id_publicacao) VALUES (:uid, :pid)')->execute([
        'uid' => $userId,
        'pid' => $idPublicacao,
    ]);
    $liked = true;
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM curtidas WHERE id_publicacao = :pid');
$countStmt->execute(['pid' => $idPublicacao]);
$total = (int) $countStmt->fetchColumn();

jsonResponse(200, ['liked' => $liked, 'total' => $total, 'message' => $liked ? 'Curtida adicionada' : 'Curtida removida']);
