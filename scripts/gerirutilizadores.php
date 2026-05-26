<?php
$db = new SQLite3('app.db');
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $db->exec("DELETE FROM Utilizadores WHERE idUtilizador = $id");
}
if (isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $tipo = $_POST['tipo'];
    $stmt = $db->prepare("UPDATE Utilizadores SET tipo = :tipo WHERE idUtilizador = :id");
    $stmt->bindValue(':tipo', $tipo);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
}
$result = $db->query("SELECT * FROM Utilizadores");
$dados = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $dados[] = $row;
}
header('Content-Type: application/json');
echo json_encode($dados);
?>

