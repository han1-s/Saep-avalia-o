# SAEPVision

Plataforma web de fotografia criada para a Avaliação Prática de Desempenho do
SAEP/SENAI, no curso Técnico em Informática para Internet.

**Estudante / Desenvolvedor:** Haniel Sousa e Souza

## Sobre o projeto

O SAEPVision permite visualizar e pesquisar publicações de fotógrafos, curtir
fotos e, com uma conta de fotógrafo, publicar e excluir imagens. A interface é
uma SPA em HTML, CSS e JavaScript; o backend usa PHP e persiste usuários,
publicações e curtidas em MySQL ou SQLite.

As imagens iniciais e o avatar padrão ficam dentro deste repositório, em
`assets/img/fotografias/` e `assets/img/imagens_usuarios/`. Assim, a aplicação
não depende da pasta externa de anexos da avaliação para exibir a galeria.
Novas fotos enviadas por fotógrafos também são gravadas em `assets/img/`.

## Estrutura

```text
assets/
  css/style.css
  fonts/                         Fonte Inter e licença OFL
  icons/                         Ícones SVG da interface
  img/fotografias/               Nove fotos iniciais da galeria
  img/imagens_usuarios/          Avatares e imagem padrão
  js/                            API, autenticação e interface
backend/
  api/                           Cadastro, publicações e curtidas
  auth/                          Login e logout
  config/database.php            Conexão e inicialização do banco
  data/usuarios.csv              Dados legados de usuários
  media.php                      Entrega de arquivos de mídia
database.sql                     Estrutura e carga inicial do MySQL
index.html                       Página principal
```

## Requisitos

- PHP 8.0 ou superior.
- MySQL com PDO MySQL, ou PDO SQLite e SQLite3 para usar o modo SQLite.
- Permissão de escrita em `assets/img/` para publicar fotos.

## Executar localmente

Abra um terminal na pasta `vision_saep Haniel Sousa e Souza`.

Para MySQL, configure `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` conforme seu
ambiente e execute `database.sql` uma vez em um banco vazio. O script carrega os
usuários do CSV, converte o tipo legado `usuario` para `comum` e cria as nove
publicações de exemplo. A carga do CSV usa `LOAD DATA LOCAL INFILE`; habilite
essa opção no cliente e no servidor MySQL e execute o comando a partir da pasta
do projeto, por exemplo:

```powershell
cmd /c "mysql --local-infile=1 -u root -p < database.sql"
```

Se a conexão MySQL não estiver disponível e as extensões PDO SQLite e SQLite3
estiverem habilitadas, o backend usa SQLite automaticamente. Nesse modo, o
banco fica em um arquivo temporário do sistema e os dados de demonstração são
criados na primeira conexão. `SAEPVISION_SQLITE_PATH` permite definir outro
caminho para esse arquivo.

Inicie o servidor PHP com a pasta do projeto como raiz:

```powershell
php -S 127.0.0.1:8000
```

Abra `http://127.0.0.1:8000/`. O diretório `assets/img/` precisa continuar
gravável pelo usuário do servidor para que uploads funcionem.

## Observações

- Hospede o projeto na raiz do servidor web: os endpoints usam caminhos como
  `/backend/api/...`.
- Os arquivos de mídia são acessados pelo endpoint `backend/media.php`.
- O arquivo `backend/data/usuarios.csv` contém contas de demonstração para a
  carga inicial; substitua os dados de exemplo antes de usar em produção.
- O MySQL e o SQLite são inicializados de formas distintas: o script SQL deve
  ser executado apenas uma vez em um banco vazio; no SQLite, a criação e a carga
  inicial são automáticas.
