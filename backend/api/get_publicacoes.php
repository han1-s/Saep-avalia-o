<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$pdo = db();
$userId = $_SESSION['user_id'] ?? null;
$search = trim((string) ($_GET['fotografo'] ?? ''));
$meus = !empty($_GET['somente_meu']);

$sql = "
    SELECT
        p.id,
        p.titulo,
        p.local_origem,
        p.caminho_imagem,
        u.id_usuario AS id_fotografo,
        u.nome,
        u.nome_usuario,
        u.imagem_usuario,
        COUNT(c.id) AS total_curtidas,
        MAX(CASE WHEN c.id_usuario = :userId THEN 1 ELSE 0 END) AS liked_by_current_user
    FROM publicacoes p
    INNER JOIN usuarios u ON u.id_usuario = p.id_fotografo
    LEFT JOIN curtidas c ON c.id_publicacao = p.id
";

$params = ['userId' => $userId];
$where = [];

if ($search !== '') {
    $where[] = 'u.nome_usuario LIKE :fotografo';
    $params['fotografo'] = '%' . $search . '%';
}

if ($meus && $userId) {
    $where[] = 'p.id_fotografo = :currentPhotographer';
    $params['currentPhotographer'] = $userId;
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' GROUP BY p.id, p.titulo, p.local_origem, p.caminho_imagem, u.id_usuario, u.nome, u.nome_usuario, u.imagem_usuario ORDER BY p.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$publicacoes = [];
foreach ($rows as $row) {
    $publicacoes[] = [
        'id' => (int) $row['id'],
        'titulo' => $row['titulo'],
        'local_origem' => $row['local_origem'],
        'fotografo_nome' => $row['nome'],
        'fotografo_usuario' => $row['nome_usuario'],
        'fotografo_imagem' => $row['imagem_usuario'],
        'total_curtidas' => (int) $row['total_curtidas'],
        'liked_by_current_user' => (bool) (int) $row['liked_by_current_user'],
        'imagem_url' => buildMediaUrl($row['caminho_imagem']),
        'perfil_url' => buildMediaUrl($row['imagem_usuario'] ?? 'assets/img/imagens_usuarios/saepVision.jpeg'),
        'is_owner' => $userId && (int) $row['id_fotografo'] === (int) $userId,
    ];
}

jsonResponse(200, ['publicacoes' => $publicacoes, 'total' => count($publicacoes)]);
