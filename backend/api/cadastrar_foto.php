<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!userIsAuthenticated() || !userIsPhotographer()) {
    jsonResponse(403, ['message' => 'Acesso restrito a fotógrafos autenticados']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['message' => 'Método não permitido']);
}

$titulo = trim((string) ($_POST['titulo'] ?? ''));
$local = trim((string) ($_POST['local_origem'] ?? ''));

if ($titulo === '') {
    jsonResponse(400, ['message' => 'Título da imagem obrigatório']);
}

if ($local === '') {
    jsonResponse(400, ['message' => 'Fonte de origem obrigatória']);
}

if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(400, ['message' => 'Nenhum arquivo escolhido']);
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$fileName = $_FILES['imagem']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed, true)) {
    jsonResponse(400, ['message' => 'Formato de imagem não permitido']);
}

$uploadDir = getProjectRoot() . '/assets/img';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = uniqid('foto_', true) . '.' . $ext;
$target = $uploadDir . '/' . $filename;
if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $target)) {
    jsonResponse(500, ['message' => 'Erro ao salvar a imagem']);
}

$relative = 'assets/img/' . $filename;
$pdo = db();
$stmt = $pdo->prepare('INSERT INTO publicacoes (id_fotografo, titulo, local_origem, caminho_imagem) VALUES (:userId, :titulo, :local, :image)');
$stmt->execute([
    'userId' => $_SESSION['user_id'],
    'titulo' => $titulo,
    'local' => $local,
    'image' => $relative,
]);

jsonResponse(201, ['message' => 'Foto cadastrada com sucesso', 'imagem' => buildMediaUrl($relative)]);
