<?php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$requested = trim((string) ($_GET['path'] ?? ''));
if ($requested === '') {
    http_response_code(400);
    exit('Arquivo não informado');
}

$projectRoot = getProjectRoot();
$supportRoot = getSupportRoot();
$fallbackSupportRoot = realpath('C:/Users/Aluno.candeias/Documents/Pasta Documento de Apoio Avaliação SAEP Informática para Internet/Estudante/anexos_prova/anexos_prova') ?: $supportRoot;
$requestedPath = ltrim($requested, '/');
$candidates = [
    $projectRoot . '/' . $requestedPath,
    $supportRoot . '/' . $requestedPath,
    $fallbackSupportRoot . '/' . $requestedPath,
];

if (str_starts_with($requestedPath, '../Estudante/') || str_contains($requestedPath, 'Estudante/anexos_prova/anexos_prova/')) {
    $trimmed = preg_replace('#^(?:\.\./)?Estudante/anexos_prova/anexos_prova/?#', '', $requestedPath);
    $candidates[] = $fallbackSupportRoot . '/' . ltrim($trimmed, '/');
    $candidates[] = $supportRoot . '/' . ltrim($trimmed, '/');
}

$resolved = null;
foreach ($candidates as $candidate) {
    $real = realpath($candidate);
    if ($real !== false) {
        $resolved = $real;
        break;
    }
}

if ($resolved === null || !is_file($resolved)) {
    http_response_code(404);
    exit('Arquivo não encontrado');
}

$projectReal = realpath($projectRoot);
$supportReal = realpath($supportRoot);
$allowed = false;
if ($projectReal !== false && strncmp($resolved, $projectReal, strlen($projectReal)) === 0) {
    $allowed = true;
}
if ($supportReal !== false && strncmp($resolved, $supportReal, strlen($supportReal)) === 0) {
    $allowed = true;
}

if (!$allowed) {
    http_response_code(403);
    exit('Acesso negado');
}

$mime = function_exists('mime_content_type') ? mime_content_type($resolved) : null;
if (!$mime && function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo !== false ? finfo_file($finfo, $resolved) : null;
    if (is_resource($finfo) || $finfo instanceof finfo) {
        finfo_close($finfo);
    }
}

$mime = $mime ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=3600');
header('Content-Length: ' . filesize($resolved));
readfile($resolved);
