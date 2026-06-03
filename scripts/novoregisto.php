<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.html");
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($nome === '' || $username === '' || $email === '' || $password === '') {
    header("Location: ../login.html?registo=campos");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../login.html?registo=email_invalido");
    exit;
}

$stmt = $db->prepare("
    SELECT id 
    FROM utilizadores 
    WHERE username = :username OR email = :email
");

$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);

$result = $stmt->execute();

if ($result->fetchArray(SQLITE3_ASSOC)) {
    header("Location: ../login.html?registo=duplicado");
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("
    INSERT INTO utilizadores (nome, username, email, password, tipo, ultimo_acesso)
    VALUES (:nome, :username, :email, :password, 'cliente', NULL)
");

$stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);
$stmt->bindValue(':password', $hash, SQLITE3_TEXT);

if ($stmt->execute()) {
    header("Location: ../login.html?registo=sucesso");
    exit;
}

header("Location: ../login.html?registo=erro");
exit;
?>
