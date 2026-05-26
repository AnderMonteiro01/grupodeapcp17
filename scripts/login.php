<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método inválido.");
}

$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($login === '' || $password === '') {
    die("Username/email e palavra-passe são obrigatórios.");
}

$stmt = $db->prepare("
    SELECT id, nome, username, email, password, tipo
    FROM utilizadores
    WHERE username = :login OR email = :login
");

$stmt->bindValue(':login', $login, SQLITE3_TEXT);

$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    die("Credenciais inválidas.");
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['nome'] = $user['nome'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['tipo'] = $user['tipo'];

$dataHora = date('Y-m-d H:i:s');

$stmt = $db->prepare("
    UPDATE utilizadores
    SET ultimo_acesso = :ultimo_acesso
    WHERE id = :id
");

$stmt->bindValue(':ultimo_acesso', $dataHora, SQLITE3_TEXT);
$stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
$stmt->execute();

$stmt = $db->prepare("
    INSERT INTO acessos (utilizador_id, data_hora)
    VALUES (:utilizador_id, :data_hora)
");

$stmt->bindValue(':utilizador_id', $user['id'], SQLITE3_INTEGER);
$stmt->bindValue(':data_hora', $dataHora, SQLITE3_TEXT);
$stmt->execute();

if ($user['tipo'] === 'admin') {
    header("Location: ../paineladmin.html");
    exit;
}

if ($user['tipo'] === 'restaurante') {
    header("Location: ../painelrestaurante.php");
    exit;
}

header("Location: ../restaurantes.php");
exit;
?>