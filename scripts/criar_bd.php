<?php
require_once 'db.php';

$db->exec("
CREATE TABLE IF NOT EXISTS utilizadores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    tipo TEXT NOT NULL CHECK(tipo IN ('cliente', 'restaurante', 'admin')),
    ultimo_acesso TEXT
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS restaurantes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    utilizador_id INTEGER,
    nome TEXT NOT NULL,
    categoria TEXT,
    morada TEXT,
    ativo INTEGER DEFAULT 1,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id)
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS produtos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurante_id INTEGER NOT NULL,
    nome TEXT NOT NULL,
    descricao TEXT,
    preco REAL NOT NULL,
    disponivel INTEGER DEFAULT 1,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS encomendas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    utilizador_id INTEGER NOT NULL,
    data TEXT NOT NULL,
    estado TEXT NOT NULL DEFAULT 'pendente',
    total REAL NOT NULL DEFAULT 0,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id)
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS encomenda_itens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    encomenda_id INTEGER NOT NULL,
    produto_id INTEGER NOT NULL,
    quantidade INTEGER NOT NULL,
    preco_unitario REAL NOT NULL,
    FOREIGN KEY (encomenda_id) REFERENCES encomendas(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS acessos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    utilizador_id INTEGER NOT NULL,
    data_hora TEXT NOT NULL,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id)
);
");

echo "<h1>Base de dados criada com sucesso.</h1>";
echo "<p>As tabelas da aplicação FoodToGo foram criadas ou já existiam.</p>";
?>