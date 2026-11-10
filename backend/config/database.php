<?php

function getProjectRoot(): string
{
    return dirname(__DIR__, 2);
}

function getSqlitePath(): string
{
    $override = getenv('SAEPVISION_SQLITE_PATH');
    if (is_string($override) && trim($override) !== '') {
        return trim($override);
    }

    $tempDir = sys_get_temp_dir();
    return rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'saepvision.sqlite';
}

function getSupportRoot(): string
{
    $projectRoot = getProjectRoot();
    $candidates = [
        $projectRoot . '/../Estudante/anexos_prova/anexos_prova',
        dirname($projectRoot) . '/Estudante/anexos_prova/anexos_prova',
        'C:/Users/Aluno.candeias/Documents/Pasta Documento de Apoio Avaliação SAEP Informática para Internet/Estudante/anexos_prova/anexos_prova',
    ];

    foreach ($candidates as $candidate) {
        $real = realpath($candidate);
        if ($real !== false) {
            return $real;
        }
    }

    return $projectRoot . '/../Estudante/anexos_prova/anexos_prova';
}

function db(): PDO
{
    $host = getenv('DB_HOST') ?: 'localhost';
    $name = getenv('DB_NAME') ?: 'saepvision';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    try {
        $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        ]);
        return $pdo;
    } catch (Throwable $e) {
        if (!extension_loaded('pdo_sqlite') || !extension_loaded('sqlite3')) {
            throw new RuntimeException('MySQL não está disponível e a extensão SQLite do PHP não está habilitada. Ative php_pdo_sqlite e php_sqlite3 no arquivo php.ini.', 0, $e);
        }

        try {
            $sqlitePath = getSqlitePath();
            $pdo = new PDO('sqlite:' . str_replace('\\', '/', $sqlitePath), null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            ensureSqliteSchema($pdo);
            return $pdo;
        } catch (Throwable $sqliteException) {
            throw new RuntimeException('Não foi possível inicializar o banco. Verifique o serviço MySQL ou habilite a extensão SQLite do PHP.', 0, $sqliteException);
        }
    }
}

function ensureSqliteSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        nome_usuario TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        senha TEXT NOT NULL,
        imagem_usuario TEXT DEFAULT NULL,
        tipo TEXT CHECK(tipo IN ('comum','fotografo')) NOT NULL DEFAULT 'comum',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS publicacoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_fotografo INTEGER NOT NULL,
        titulo TEXT NOT NULL,
        local_origem TEXT NOT NULL,
        caminho_imagem TEXT NOT NULL,
        FOREIGN KEY (id_fotografo) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS curtidas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_usuario INTEGER NOT NULL,
        id_publicacao INTEGER NOT NULL,
        UNIQUE(id_usuario, id_publicacao),
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
        FOREIGN KEY (id_publicacao) REFERENCES publicacoes(id) ON DELETE CASCADE
    )");

    $count = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    if ($count === 0) {
        $seed = [
            [1, 'fotografo1', 'fotografo_1', 'fotografo1@email.com', '123456', 'assets/img/imagens_usuarios/fotografo1.jpg', 'fotografo'],
            [2, 'fotografo2', 'fotografo_2', 'fotografo2@gmail.com', '123456', 'assets/img/imagens_usuarios/fotografo2.jpg', 'fotografo'],
            [3, 'fotografo3', 'fotografo_3', 'fotografo3@gmail.com', '123456', 'assets/img/imagens_usuarios/fotografo3.jpg', 'fotografo'],
            [4, 'usuario1', 'usuario_1', 'usuario1@gmail.com', '123456', 'assets/img/imagens_usuarios/usuario1.jpg', 'comum'],
            [5, 'usuario2', 'usuario_2', 'usuario2@gmail.com', '123456', 'assets/img/imagens_usuarios/usuario2.jpg', 'comum'],
            [6, 'usuario3', 'usuario_3', 'usuario3@gmail.com', '123456', 'assets/img/imagens_usuarios/usuario3.jpg', 'comum'],
        ];

        $insert = $pdo->prepare('INSERT INTO usuarios (id_usuario, nome, nome_usuario, email, senha, imagem_usuario, tipo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))');
        foreach ($seed as $row) {
            $insert->execute($row);
        }
    }

    $publicacoesCount = (int) $pdo->query('SELECT COUNT(*) FROM publicacoes')->fetchColumn();
    if ($publicacoesCount === 0) {
        $seed = [
            [1, 1, 'Foto 1', 'Rio de Janeiro', 'assets/img/fotografias/foto1.jpg'],
            [2, 1, 'Foto 2', 'Rio de Janeiro', 'assets/img/fotografias/foto2.jpg'],
            [3, 1, 'Foto 3', 'Rio de Janeiro', 'assets/img/fotografias/foto3.jpg'],
            [4, 2, 'Foto 4', 'São Paulo', 'assets/img/fotografias/foto4.jpg'],
            [5, 2, 'Foto 5', 'São Paulo', 'assets/img/fotografias/foto5.jpg'],
            [6, 2, 'Foto 6', 'São Paulo', 'assets/img/fotografias/foto6.jpg'],
            [7, 3, 'Foto 7', 'Salvador', 'assets/img/fotografias/foto7.jpg'],
            [8, 3, 'Foto 8', 'Salvador', 'assets/img/fotografias/foto8.jpg'],
            [9, 3, 'Foto 9', 'Salvador', 'assets/img/fotografias/foto9.jpg'],
        ];

        $insert = $pdo->prepare('INSERT INTO publicacoes (id, id_fotografo, titulo, local_origem, caminho_imagem) VALUES (?, ?, ?, ?, ?)');
        foreach ($seed as $row) {
            $insert->execute($row);
        }
    }
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id_usuario = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function userIsAuthenticated(): bool
{
    return !empty($_SESSION['user_id']);
}

function userIsPhotographer(): bool
{
    $user = currentUser();
    return $user && $user['tipo'] === 'fotografo';
}

function buildMediaUrl(string $relativePath): string
{
    $normalized = trim((string) $relativePath);
    if ($normalized === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#', $normalized) === 1) {
        return $normalized;
    }

    if (str_starts_with($normalized, '../') || str_starts_with($normalized, './') || str_starts_with($normalized, 'Estudante/') || str_starts_with($normalized, 'assets/')) {
        // keep explicit support-root or project-root paths as they are
    } else {
        $supportPrefix = '../Estudante/anexos_prova/anexos_prova/';
        if (str_contains($normalized, '/')) {
            $normalized = $supportPrefix . ltrim($normalized, '/');
        } else {
            $normalized = $supportPrefix . 'imagens_usuarios/' . ltrim($normalized, '/');
        }
    }

    return '/backend/media.php?path=' . rawurlencode($normalized);
}

function jsonResponse(int $status, $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
