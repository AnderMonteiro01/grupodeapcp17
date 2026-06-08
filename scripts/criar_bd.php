<?php
require_once 'db.php';

/*
    FoodToGo - criar_bd.php
    Cria/atualiza a base de dados SQLite da aplicação.

    Pode ser executado várias vezes.
    Se a base já existir, tenta acrescentar colunas novas sem apagar dados.
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
        utilizador_id INTEGER NOT NULL,
        restaurante_id INTEGER NOT NULL,
        data TEXT NOT NULL,
        estado TEXT NOT NULL DEFAULT 'recebida',
        total REAL NOT NULL DEFAULT 0,
        morada_entrega TEXT,
        contacto_cliente TEXT,
        observacoes TEXT,
        FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id),
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
    )
");

/*
    Se a tabela encomendas já existia antes, o CREATE TABLE IF NOT EXISTS
    não altera a estrutura. Por isso verificamos e adicionamos colunas em falta.
*/

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

function criarProdutoSeNaoExiste($db, $restauranteId, $nome, $descricao, $preco) {
    $stmt = $db->prepare("
        SELECT id
        FROM produtos
        WHERE restaurante_id = :restaurante_id
          AND nome = :nome
        LIMIT 1
    ");

    $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
    $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);

    $result = $stmt->execute();
    $produto = $result->fetchArray(SQLITE3_ASSOC);

    if ($produto) {
        $stmt = $db->prepare("
            UPDATE produtos
            SET descricao = :descricao,
                preco = :preco,
                disponivel = 1
            WHERE id = :id
        ");

        $stmt->bindValue(':descricao', $descricao, SQLITE3_TEXT);
        $stmt->bindValue(':preco', (float)$preco, SQLITE3_FLOAT);
        $stmt->bindValue(':id', (int)$produto['id'], SQLITE3_INTEGER);
        $stmt->execute();

        return;
    }

    $stmt = $db->prepare("
        INSERT INTO produtos (
            restaurante_id,
            nome,
            descricao,
            preco,
            disponivel
        )
        VALUES (
            :restaurante_id,
            :nome,
            :descricao,
            :preco,
            1
        )
    ");

    $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
    $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
    $stmt->bindValue(':descricao', $descricao, SQLITE3_TEXT);
    $stmt->bindValue(':preco', (float)$preco, SQLITE3_FLOAT);
    $stmt->execute();
}

/* =========================
   UTILIZADORES DE TESTE
========================= */

$adminId = criarOuAtualizarUtilizador(
    $db,
    'Administrador',
    'admin',
    'admin@foodtogo.pt',
    'admin123',
    'admin'
);

$restauranteUserId = criarOuAtualizarUtilizador(
    $db,
    'Restaurante Teste',
    'restaurante',
    'restaurante@foodtogo.pt',
    'rest123',
    'restaurante'
);

$clienteId = criarOuAtualizarUtilizador(
    $db,
    'Cliente Teste',
    'cliente',
    'cliente@foodtogo.pt',
    'cliente123',
    'cliente'
);

/* =========================
   RESTAURANTE DE TESTE
========================= */

$stmt = $db->prepare("
    SELECT id
    FROM restaurantes
    WHERE utilizador_id = :utilizador_id
    LIMIT 1
");

$stmt->bindValue(':utilizador_id', $restauranteUserId, SQLITE3_INTEGER);
$result = $stmt->execute();
$restauranteTeste = $result->fetchArray(SQLITE3_ASSOC);

if ($restauranteTeste) {
    $restauranteId = (int)$restauranteTeste['id'];

    $stmt = $db->prepare("
        UPDATE restaurantes
        SET nome = :nome,
            categoria = :categoria,
            morada = :morada,
            ativo = 1
        WHERE id = :id
    ");

    $stmt->bindValue(':nome', 'Sabor Caseiro', SQLITE3_TEXT);
    $stmt->bindValue(':categoria', 'Comida portuguesa', SQLITE3_TEXT);
    $stmt->bindValue(':morada', 'Rua Teste 123, Porto', SQLITE3_TEXT);
    $stmt->bindValue(':id', $restauranteId, SQLITE3_INTEGER);
    $stmt->execute();
} else {
    $stmt = $db->prepare("
        INSERT INTO restaurantes (
            utilizador_id,
            nome,
            categoria,
            morada,
            ativo
        )
        VALUES (
            :utilizador_id,
            :nome,
            :categoria,
            :morada,
            1
        )
    ");

    $stmt->bindValue(':utilizador_id', $restauranteUserId, SQLITE3_INTEGER);
    $stmt->bindValue(':nome', 'Sabor Caseiro', SQLITE3_TEXT);
    $stmt->bindValue(':categoria', 'Comida portuguesa', SQLITE3_TEXT);
    $stmt->bindValue(':morada', 'Rua Teste 123, Porto', SQLITE3_TEXT);
    $stmt->execute();

    $restauranteId = (int)$db->lastInsertRowID();
}

/* =========================
   PRODUTOS DE TESTE
========================= */

criarProdutoSeNaoExiste(
    $db,
    $restauranteId,
    'Frango grelhado com arroz',
    'Menu com arroz, salada e bebida incluída.',
    9.50
);

criarProdutoSeNaoExiste(
    $db,
    $restauranteId,
    'Bitoque à casa',
    'Bife com ovo, batata frita, arroz e bebida.',
    10.90
);

criarProdutoSeNaoExiste(
    $db,
    $restauranteId,
    'Lasanha caseira',
    'Lasanha de carne com salada e bebida.',
    8.75
);

criarProdutoSeNaoExiste(
    $db,
    $restauranteId,
    'Hambúrguer de Vaca + Sumo Compal 300ml + Sorvete Baunilha',
    'Combo completo.',
    19.50
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

            <h2>Utilizadores de teste</h2>

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

                    <tr>
                        <td>Restaurante</td>
                        <td>restaurante</td>
                        <td>rest123</td>
                    </tr>

                    <tr>
                        <td>Cliente</td>
                        <td>cliente</td>
                        <td>cliente123</td>
                    </tr>
                </tbody>
            </table>

            <p>
                Restaurante de teste associado ao utilizador <strong>restaurante</strong>:
                <strong>Sabor Caseiro</strong>.
            </p>

            <p>
                <a href="../login.html" class="botao-verde">Ir para login</a>
                <a href="../restaurantes.php" class="botao-laranja">Ver restaurantes</a>
            </p>
        </section>
    </main>
</body>
</html>