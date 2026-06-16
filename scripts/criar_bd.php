<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'Script de manutenção disponível apenas por linha de comandos.';
    exit;
}

require_once 'db.php';

/*
    FoodToGo - criar_bd.php
    Cria/atualiza a base de dados SQLite da aplicação.

    O seed inicial configura apenas o administrador.
    Clientes podem ser criados pelo registo.
    Restaurantes devem ser criados e associados pelo painel de administração.
*/

$db->exec("PRAGMA foreign_keys = ON");

/* =========================
   TABELA UTILIZADORES
========================= */

$db->exec("
    CREATE TABLE IF NOT EXISTS utilizadores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        tipo TEXT NOT NULL DEFAULT 'cliente',
        ultimo_acesso TEXT,
        CHECK (tipo IN ('cliente', 'restaurante', 'admin'))
    )
");

/* =========================
   TABELA RESTAURANTES
========================= */

$db->exec("
    CREATE TABLE IF NOT EXISTS restaurantes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        utilizador_id INTEGER UNIQUE,
        nome TEXT NOT NULL,
        categoria TEXT,
        morada TEXT,
        ativo INTEGER NOT NULL DEFAULT 1,
        FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id)
    )
");

/* =========================
   TABELA PRODUTOS
========================= */

$db->exec("
    CREATE TABLE IF NOT EXISTS produtos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        restaurante_id INTEGER NOT NULL,
        nome TEXT NOT NULL,
        descricao TEXT,
        preco REAL NOT NULL,
        disponivel INTEGER NOT NULL DEFAULT 1,
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
    )
");

/* =========================
   TABELA ENCOMENDAS
========================= */

$db->exec("
    CREATE TABLE IF NOT EXISTS encomendas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        utilizador_id INTEGER,
        restaurante_id INTEGER NOT NULL,
        data TEXT NOT NULL,
        estado TEXT NOT NULL DEFAULT 'recebida',
        total REAL NOT NULL DEFAULT 0,
        morada_entrega TEXT,
        contacto_cliente TEXT,
        observacoes TEXT,
        FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE SET NULL,
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
    )
");

/* Colunas acrescentadas para bases criadas em versões anteriores. */
$colunasEncomendas = [];
$result = $db->query("PRAGMA table_info(encomendas)");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $colunasEncomendas[] = $row['name'];
}

if (!in_array('restaurante_id', $colunasEncomendas, true)) {
    $db->exec("ALTER TABLE encomendas ADD COLUMN restaurante_id INTEGER");
}

if (!in_array('morada_entrega', $colunasEncomendas, true)) {
    $db->exec("ALTER TABLE encomendas ADD COLUMN morada_entrega TEXT");
}

if (!in_array('contacto_cliente', $colunasEncomendas, true)) {
    $db->exec("ALTER TABLE encomendas ADD COLUMN contacto_cliente TEXT");
}

if (!in_array('observacoes', $colunasEncomendas, true)) {
    $db->exec("ALTER TABLE encomendas ADD COLUMN observacoes TEXT");
}

garantir_encomendas_permitem_cliente_apagado($db);

/* =========================
   TABELA ENCOMENDA_ITENS
========================= */

$db->exec("
    CREATE TABLE IF NOT EXISTS encomenda_itens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        encomenda_id INTEGER NOT NULL,
        produto_id INTEGER NOT NULL,
        quantidade INTEGER NOT NULL,
        preco_unitario REAL NOT NULL,
        FOREIGN KEY (encomenda_id) REFERENCES encomendas(id),
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
    )
");

/* =========================
   TABELA ACESSOS
========================= */

$db->exec("
    CREATE TABLE IF NOT EXISTS acessos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        utilizador_id INTEGER NOT NULL,
        data_hora TEXT NOT NULL,
        FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id)
    )
");

/* =========================
   FUNÇÕES AUXILIARES
========================= */

function criarOuAtualizarUtilizador($db, $nome, $username, $email, $password, $tipo) {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        SELECT id
        FROM utilizadores
        WHERE username = :username OR email = :email
        LIMIT 1
    ");

    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);

    $result = $stmt->execute();
    $utilizador = $result->fetchArray(SQLITE3_ASSOC);

    if ($utilizador) {
        $stmt = $db->prepare("
            UPDATE utilizadores
            SET nome = :nome,
                username = :username,
                email = :email,
                password = :password,
                tipo = :tipo
            WHERE id = :id
        ");

        $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':password', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':tipo', $tipo, SQLITE3_TEXT);
        $stmt->bindValue(':id', (int)$utilizador['id'], SQLITE3_INTEGER);
        $stmt->execute();

        return (int)$utilizador['id'];
    }

    $stmt = $db->prepare("
        INSERT INTO utilizadores (
            nome,
            username,
            email,
            password,
            tipo,
            ultimo_acesso
        )
        VALUES (
            :nome,
            :username,
            :email,
            :password,
            :tipo,
            NULL
        )
    ");

    $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':password', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':tipo', $tipo, SQLITE3_TEXT);
    $stmt->execute();

    return (int)$db->lastInsertRowID();
}

/* =========================
   UTILIZADOR INICIAL
========================= */

criarOuAtualizarUtilizador(
    $db,
    'Administrador',
    'admin',
    'admin@foodtogo.pt',
    'admin123',
    'admin'
);

/* =========================
   OUTPUT
========================= */

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Base de Dados | FoodToGo</title>
    <link rel="stylesheet" href="../styles/styles.css">
</head>

<body class="pagina-painel">
    <main>
        <section class="painel-container">
            <h1>FoodToGo</h1>

            <div class="mensagem-ok">
                Base de dados criada/atualizada com sucesso.
            </div>

            <h2>Utilizador inicial</h2>

            <table class="tabela-restaurantes">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Username</th>
                        <th>Password</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Administrador</td>
                        <td>admin</td>
                        <td>admin123</td>
                    </tr>
                </tbody>
            </table>

            <p>
                A base fica apenas com o administrador inicial.
                Clientes podem ser criados pelo registo e associados a restaurantes pelo painel de administração.
            </p>

            <p>
                <a href="../login.html" class="botao-verde">Ir para login</a>
                <a href="../restaurantes.php" class="botao-laranja">Ver restaurantes</a>
            </p>
        </section>
    </main>
</body>
</html>
