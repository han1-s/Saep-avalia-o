<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!userIsAuthenticated()) {
    jsonResponse(200, ['user' => null]);
}

$user = currentUser();
if (!$user) {
    jsonResponse(200, ['user' => null]);
}

jsonResponse(200, ['user' => [
    'id' => (int) $user['id_usuario'],
    'nome' => $user['nome'],
    'nome_usuario' => $user['nome_usuario'],
    'tipo' => $user['tipo'],
    'imagem_usuario' => $user['imagem_usuario'],
]]);
