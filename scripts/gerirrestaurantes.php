<?php
$db = new SQLite3('app.db');
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $db->exec("DELETE FROM Restaurantes WHERE idRestaurante = $id");
}
if (isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $nome = $_POST['nome'];
    $stmt = $db->prepare("UPDATE Restaurantes SET nome = :nome WHERE idRestaurante = :id");
    $stmt->bindValue(':nome', $nome);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
}
$result = $db->query("SELECT * FROM Restaurantes");

$dados = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $dados[] = $row;
}
header('Content-Type: application/json');
echo json_encode($dados);
?>
