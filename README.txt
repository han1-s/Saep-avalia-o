# 📸 SAEPVision - Plataforma Web de Fotografia

> **Avaliação Prática de Desempenho - SAEP (SENAI)**  
> **Curso:** Técnico em Informática para Internet  
> **Estudante / Desenvolvedor:** Haniel Sousa e Souza  
> **Projeto:** Desenvolvimento Full Stack Dinâmico (Single Page Application)

---

## 📌 Visão Geral do Projeto

O **SAEPVision** é uma plataforma web voltada para fotógrafos profissionais e entusiastas compartilharem seus portfólios, interagirem com a comunidade e gerenciarem publicações visuais. 

Este repositório contém a solução completa para o desafio prático da avaliação SAEP do SENAI. A aplicação foi transformada de um protótipo estático para uma **arquitetura 100% dinâmica**, onde todas as informações (usuários, postagens, curtidas e perfis) são carregadas e persistidas diretamente em um **Banco de Dados Relacional MySQL**, operando sem a necessidade de recarregar a página (*Single Page Application*).

---

## 🔍 Análise da Pasta de Apoio e Pré-requisitos

Conforme as instruções da avaliação, todos os ativos de suporte utilizados no projeto foram inspecionados e extraídos do diretório de avaliação localizado no Desktop:
`Desktop/Pasta Documento de Apoio Avaliação SAEP informática para internet/`

Os seguintes arquivos de apoio foram integrados ao projeto:
- `usuarios.csv`: Arquivo de carga legada contendo os usuários pré-cadastrados para migração inicial no banco de dados.
- **Vetores SVG:** Ícones de interface obrigatórios (`coracao.svg`, `lupa.svg`, `instagram.svg`, `tiktok.svg`, `linkedin.svg`, `youtube.svg`).

---

## 📂 Estrutura de Pastas e Arquivos

O projeto foi inteiramente estruturado na pasta principal de entrega: `vision_saep Haniel Sousa e Souza/`.

```text
vision_saep Haniel Sousa e Souza/
├── index.html                  # Interface única da aplicação (SPA sem reload)
├── assets/
│   ├── css/
│   │   └── style.css           # Estilos globais (Fonte Inter, Paleta Hex/RGB, Grid 3 colunas)
│   ├── js/
│   │   ├── app.js              # Manipulação do DOM, renderização da galeria e eventos
│   │   ├── auth.js             # Gestão de Login, Logout e modais
│   │   └── api.js              # Comunicação assíncrona (Fetch/AJAX) com o backend
│   ├── img/                    # Uploads e fotos de perfil dos usuários
│   └── icons/                  # Ícones vetoriais SVG (coracao, redes sociais, busca)
├── backend/
│   ├── config/
│   │   └── database.php        # Conexão PDO com o MySQL (UTF-8)
│   ├── auth/
│   │   ├── login.php           # Autenticação e retorno de sessão/token
│   │   └── logout.php          # Destruição da sessão ativa
│   ├── api/
│   │   ├── get_publicacoes.php # API da galeria (suporta busca e filtro de fotos próprias)
│   │   ├── curtir.php          # Incremento/decremento de curtidas (máx 1 por foto)
│   │   ├── cadastrar_foto.php  # Upload de mídia e inclusão de registro no banco
│   │   └── deletar_foto.php    # Exclusão física do arquivo e do registro relacional
│   └── data/
│       └── usuarios.csv        # Dados legados para carga inicial
└── database.sql                # Script SQL de criação do banco e importação do CSV
