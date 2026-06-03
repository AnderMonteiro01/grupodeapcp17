<?php
require_once 'db.php';

$db->exec("CREATE TABLE IF NOT EXISTS utilizadores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    tipo TEXT NOT NULL CHECK(tipo IN ('cliente','restaurante','admin')) DEFAULT 'cliente',
    ultimo_acesso TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS restaurantes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    utilizador_id INTEGER UNIQUE,
    nome TEXT NOT NULL,
    categoria TEXT,
    morada TEXT,
    ativo INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE SET NULL
)");

$db->exec("CREATE TABLE IF NOT EXISTS produtos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurante_id INTEGER NOT NULL,
    nome TEXT NOT NULL,
    descricao TEXT,
    preco REAL NOT NULL,
    disponivel INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
)");

$db->exec("CREATE TABLE IF NOT EXISTS encomendas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    utilizador_id INTEGER NOT NULL,
    restaurante_id INTEGER NOT NULL,
    data TEXT NOT NULL,
    estado TEXT NOT NULL DEFAULT 'recebida',
    total REAL NOT NULL DEFAULT 0,
    observacoes TEXT,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id),
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
)");

$db->exec("CREATE TABLE IF NOT EXISTS encomenda_itens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    encomenda_id INTEGER NOT NULL,
    produto_id INTEGER NOT NULL,
    quantidade INTEGER NOT NULL,
    preco_unitario REAL NOT NULL,
    FOREIGN KEY (encomenda_id) REFERENCES encomendas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
)");

$db->exec("CREATE TABLE IF NOT EXISTS acessos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    utilizador_id INTEGER NOT NULL,
    data_hora TEXT NOT NULL,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
)");

function upsert_user($db, $nome, $username, $email, $password, $tipo) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("SELECT id FROM utilizadores WHERE username = :username");
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        $stmt = $db->prepare("UPDATE utilizadores SET nome=:nome, email=:email, password=:password, tipo=:tipo WHERE username=:username");
    } else {
        $stmt = $db->prepare("INSERT INTO utilizadores (nome, username, email, password, tipo) VALUES (:nome, :username, :email, :password, :tipo)");
    }
    $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':password', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':tipo', $tipo, SQLITE3_TEXT);
    $stmt->execute();
}

upsert_user($db, 'Administrador', 'admin', 'admin@foodtogo.pt', 'admin123', 'admin');
upsert_user($db, 'Restaurante Teste', 'restaurante', 'restaurante@foodtogo.pt', 'rest123', 'restaurante');
upsert_user($db, 'Cliente Teste', 'cliente', 'cliente@foodtogo.pt', 'cliente123', 'cliente');

$restUserId = $db->querySingle("SELECT id FROM utilizadores WHERE username='restaurante'");
$existeRest = $db->querySingle("SELECT id FROM restaurantes WHERE utilizador_id=" . (int)$restUserId);
if (!$existeRest) {
    $stmt = $db->prepare("INSERT INTO restaurantes (utilizador_id, nome, categoria, morada, ativo) VALUES (:uid, 'Sabor Caseiro', 'Comida portuguesa', 'Rua Teste 123', 1)");
    $stmt->bindValue(':uid', $restUserId, SQLITE3_INTEGER);
    $stmt->execute();
}
$restId = $db->querySingle("SELECT id FROM restaurantes WHERE utilizador_id=" . (int)$restUserId);
$prodCount = $db->querySingle("SELECT COUNT(*) FROM produtos WHERE restaurante_id=" . (int)$restId);
if ((int)$prodCount === 0) {
    $stmt = $db->prepare("INSERT INTO produtos (restaurante_id, nome, descricao, preco, disponivel) VALUES (:rid, 'Frango grelhado com arroz', 'Menu com bebida incluída', 9.50, 1)");
    $stmt->bindValue(':rid', $restId, SQLITE3_INTEGER);
    $stmt->execute();
}
?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Base de Dados</title><link rel="stylesheet" href="../styles/styles.css"></head><body class="pagina-painel"><main><div class="painel-container"><div class="mensagem-ok"><strong>Base de dados criada/atualizada com sucesso.</strong></div><p>Utilizadores de teste:</p><ul><li>admin / admin123</li><li>restaurante / rest123</li><li>cliente / cliente123</li></ul><a class="botao-verde" href="../login.html">Ir para login</a></div></main></body></html>
