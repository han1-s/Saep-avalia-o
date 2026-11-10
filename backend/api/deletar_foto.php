<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!userIsAuthenticated() || !userIsPhotographer()) {
    jsonResponse(403, ['message' => 'Operação restrita ao fotógrafo']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$idPublicacao = (int) ($data['id'] ?? 0);
if ($idPublicacao <= 0) {
    jsonResponse(400, ['message' => 'Identificador inválido']);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT p.*, u.id_usuario AS owner_id FROM publicacoes p INNER JOIN usuarios u ON u.id_usuario = p.id_fotografo WHERE p.id = :id LIMIT 1');
$stmt->execute(['id' => $idPublicacao]);
$publicacao = $stmt->fetch();

if (!$publicacao) {
    jsonResponse(404, ['message' => 'Publicação não encontrada']);
}

if ((int) $publicacao['owner_id'] !== (int) $_SESSION['user_id']) {
    jsonResponse(403, ['message' => 'Você não pode excluir fotos de terceiros']);
}

$filePath = $publicacao['caminho_imagem'];
$projectFile = getProjectRoot() . '/' . ltrim($filePath, '/');
if (is_file($projectFile)) {
    unlink($projectFile);
}

$pdo->prepare('DELETE FROM curtidas WHERE id_publicacao = :id')->execute(['id' => $idPublicacao]);
$pdo->prepare('DELETE FROM publicacoes WHERE id = :id')->execute(['id' => $idPublicacao]);

jsonResponse(200, ['message' => 'Publicação removida com sucesso']);
