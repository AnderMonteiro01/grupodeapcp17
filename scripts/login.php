<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.html');
    exit;
}

$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($login === '' || $password === '') {
    die('Username/email e palavra-passe são obrigatórios.');
}

$stmt = $db->prepare("SELECT id, nome, username, email, password, tipo FROM utilizadores WHERE username = :login OR email = :login");
$stmt->bindValue(':login', $login, SQLITE3_TEXT);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    die('Credenciais inválidas. <a href="../login.html">Voltar</a>');
}

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['nome'] = $user['nome'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['tipo'] = $user['tipo'];

$dataHora = date('Y-m-d H:i:s');
$stmt = $db->prepare("UPDATE utilizadores SET ultimo_acesso = :ultimo_acesso WHERE id = :id");
$stmt->bindValue(':ultimo_acesso', $dataHora, SQLITE3_TEXT);
$stmt->bindValue(':id', (int)$user['id'], SQLITE3_INTEGER);
$stmt->execute();

$stmt = $db->prepare("INSERT INTO acessos (utilizador_id, data_hora) VALUES (:uid, :data)");
$stmt->bindValue(':uid', (int)$user['id'], SQLITE3_INTEGER);
$stmt->bindValue(':data', $dataHora, SQLITE3_TEXT);
$stmt->execute();

if ($user['tipo'] === 'admin') {
    header('Location: ../paineladmin.php');
} elseif ($user['tipo'] === 'restaurante') {
    header('Location: ../painelrestaurante.php');
} else {
    header('Location: ../restaurantes.php');
}
exit;
?>
