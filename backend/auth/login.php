<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$email = trim((string) ($data['email'] ?? ''));
$senha = (string) ($data['password'] ?? $data['senha'] ?? '');

if ($email === '' || $senha === '') {
    jsonResponse(400, ['message' => 'E-mail inválido ou vazio']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(400, ['message' => 'E-mail inválido ou vazio']);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(401, ['message' => 'Usuário não encontrado ou senha incorreta']);
}

$stored = (string) $user['senha'];
$valid = password_verify($senha, $stored) || $stored === $senha;

if (!$valid) {
    jsonResponse(401, ['message' => 'Usuário não encontrado ou senha incorreta']);
}

if ($stored !== $senha && !str_starts_with($stored, '$2y$')) {
    $hashed = password_hash($senha, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE usuarios SET senha = :senha WHERE id_usuario = :id')->execute([
        'senha' => $hashed,
        'id' => $user['id_usuario'],
    ]);
}

$_SESSION['user_id'] = (int) $user['id_usuario'];
$_SESSION['user_name'] = $user['nome'];
$_SESSION['user_type'] = $user['tipo'];

jsonResponse(200, [
    'message' => 'Login realizado com sucesso',
    'user' => [
        'id' => (int) $user['id_usuario'],
        'nome' => $user['nome'],
        'nome_usuario' => $user['nome_usuario'],
        'tipo' => $user['tipo'],
        'imagem_usuario' => $user['imagem_usuario'],
    ],
]);
