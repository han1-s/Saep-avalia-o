CREATE DATABASE IF NOT EXISTS saepvision CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE saepvision;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    nome_usuario VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    imagem_usuario VARCHAR(255) DEFAULT NULL,
    tipo ENUM('comum', 'fotografo') NOT NULL DEFAULT 'comum',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_email (email),
    UNIQUE KEY uq_nome_usuario (nome_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS publicacoes (
    id INT NOT NULL AUTO_INCREMENT,
    id_fotografo INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    local_origem VARCHAR(150) NOT NULL,
    caminho_imagem VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_fotografo (id_fotografo),
    CONSTRAINT fk_publicacoes_usuario FOREIGN KEY (id_fotografo) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curtidas (
    id INT NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_publicacao INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario_publicacao (id_usuario, id_publicacao),
    KEY idx_publicacao (id_publicacao),
    CONSTRAINT fk_curtidas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_curtidas_publicacao FOREIGN KEY (id_publicacao) REFERENCES publicacoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOAD DATA INFILE 'backend/data/usuarios.csv'
INTO TABLE usuarios
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(id_usuario, nome, nome_usuario, email, senha, imagem_usuario, tipo, created_at, updated_at)
SET senha = CASE WHEN senha = '' THEN NULL ELSE senha END;

-- Para reexecutar sem duplicar registros, use:
-- TRUNCATE TABLE curtidas;
-- TRUNCATE TABLE publicacoes;
-- TRUNCATE TABLE usuarios;
-- LOAD DATA INFILE ...
