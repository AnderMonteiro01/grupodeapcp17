<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método inválido.'
    ]);
    exit;
}

$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($login === '' || $password === '') {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Preencha o username/email e a palavra-passe.'
    ]);
    exit;
}

$stmt = $db->prepare("SELECT id, nome, username, email, password, tipo FROM utilizadores WHERE username = :login OR email = :login");
$stmt->bindValue(':login', $login, SQLITE3_TEXT);
$result = $stmt->execute();
$user = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Credenciais inválidas.'
    ]);
    exit;
}

session_regenerate_id(true);

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

$redirect = 'restaurantes.php';

if ($user['tipo'] === 'admin') {
    $redirect = 'paineladmin.php';
} elseif ($user['tipo'] === 'restaurante') {
    $redirect = 'painelrestaurante.php';
} elseif ($user['tipo'] === 'cliente') {
    $redirect = 'restaurantes.php';
}

echo json_encode([
    'sucesso' => true,
    'mensagem' => 'Login efetuado com sucesso.',
    'redirect' => $redirect
]);
exit;
?>
