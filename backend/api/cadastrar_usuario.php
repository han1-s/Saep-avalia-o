<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['message' => 'Método não permitido']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$nome = trim((string) ($data['nome'] ?? ''));
$nomeUsuario = trim((string) ($data['nome_usuario'] ?? $data['username'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$senha = (string) ($data['password'] ?? '');
$confirmSenha = (string) ($data['confirm_password'] ?? $data['confirmPassword'] ?? '');
$tipo = in_array((string) ($data['tipo'] ?? 'comum'), ['comum', 'fotografo'], true)
    ? (string) $data['tipo']
    : 'comum';

if ($nome === '') {
    jsonResponse(400, ['message' => 'Informe seu nome completo']);
}

if ($nomeUsuario === '') {
    jsonResponse(400, ['message' => 'Informe um nome de usuário']);
}

if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $nomeUsuario)) {
    jsonResponse(400, ['message' => 'Use apenas letras, números e os caracteres . _ - no nome de usuário']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(400, ['message' => 'E-mail inválido']);
}

if (strlen($senha) < 6) {
    jsonResponse(400, ['message' => 'A senha deve ter pelo menos 6 caracteres']);
}

if ($senha !== $confirmSenha) {
    jsonResponse(400, ['message' => 'As senhas não conferem']);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    jsonResponse(409, ['message' => 'Este e-mail já está cadastrado']);
}

$stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE nome_usuario = :nome_usuario LIMIT 1');
$stmt->execute(['nome_usuario' => $nomeUsuario]);
if ($stmt->fetch()) {
    jsonResponse(409, ['message' => 'Este nome de usuário já está em uso']);
}

$imagemPadrao = 'assets/img/imagens_usuarios/saepVision.jpeg';
$insert = $pdo->prepare('INSERT INTO usuarios (nome, nome_usuario, email, senha, imagem_usuario, tipo) VALUES (:nome, :nome_usuario, :email, :senha, :imagem_usuario, :tipo)');
$insert->execute([
    'nome' => $nome,
    'nome_usuario' => $nomeUsuario,
    'email' => $email,
    'senha' => password_hash($senha, PASSWORD_DEFAULT),
    'imagem_usuario' => $imagemPadrao,
    'tipo' => $tipo,
]);

$userId = (int) $pdo->lastInsertId();
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $nome;
$_SESSION['user_type'] = $tipo;

jsonResponse(201, ['message' => 'Cadastro realizado com sucesso', 'user' => [
    'id' => $userId,
    'nome' => $nome,
    'nome_usuario' => $nomeUsuario,
    'tipo' => $tipo,
    'imagem_usuario' => $imagemPadrao,
]]);
