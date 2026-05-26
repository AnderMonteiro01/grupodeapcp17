<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método inválido.");
}

$nome = trim($_POST['nome'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($nome === '' || $username === '' || $email === '' || $password === '') {
    die("Todos os campos são obrigatórios.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Email inválido.");
}

$stmt = $db->prepare("
    SELECT id FROM utilizadores
    WHERE username = :username OR email = :email
");

$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);

$result = $stmt->execute();

if ($result->fetchArray(SQLITE3_ASSOC)) {
    die("Username ou email já existe.");
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("
    INSERT INTO utilizadores (nome, username, email, password, tipo, ultimo_acesso)
    VALUES (:nome, :username, :email, :password, 'cliente', NULL)
");

$stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);
$stmt->bindValue(':password', $passwordHash, SQLITE3_TEXT);

if ($stmt->execute()) {
    echo "<h1>Registo efetuado com sucesso.</h1>";
    echo "<p><a href='../login.html'>Ir para login</a></p>";
} else {
    echo "Erro ao registar utilizador.";
}
?>