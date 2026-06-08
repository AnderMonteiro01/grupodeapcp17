<?php
session_start();
require_once __DIR__ . '/scripts/db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] ?? '') !== 'admin') { header('Location: login.html'); exit; }
$mensagem=''; $erro='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $acao = $_POST['acao'] ?? '';
    try {
        if ($acao === 'atualizar_utilizador') {
            $stmt=$db->prepare("UPDATE utilizadores SET nome=:nome, username=:username, email=:email, tipo=:tipo WHERE id=:id");
            $stmt->bindValue(':nome', trim($_POST['nome']), SQLITE3_TEXT);
            $stmt->bindValue(':username', trim($_POST['username']), SQLITE3_TEXT);
            $stmt->bindValue(':email', trim($_POST['email']), SQLITE3_TEXT);
            $stmt->bindValue(':tipo', $_POST['tipo'], SQLITE3_TEXT);
            $stmt->bindValue(':id', (int)$_POST['id'], SQLITE3_INTEGER);
            $stmt->execute(); $mensagem='Utilizador atualizado.';
        }
        if ($acao === 'remover_utilizador') {
            $id=(int)$_POST['id'];
            $db->exec("UPDATE restaurantes SET utilizador_id=NULL WHERE utilizador_id=$id");
            $stmt=$db->prepare("DELETE FROM utilizadores WHERE id=:id AND id != :me");
            $stmt->bindValue(':id',$id,SQLITE3_INTEGER); $stmt->bindValue(':me',(int)$_SESSION['user_id'],SQLITE3_INTEGER);
            $stmt->execute(); $mensagem='Utilizador removido.';
        }
        if ($acao === 'adicionar_restaurante') {
            $stmt=$db->prepare("INSERT INTO restaurantes (nome,categoria,morada,ativo) VALUES (:nome,:categoria,:morada,:ativo)");
            $stmt->bindValue(':nome',trim($_POST['nome']),SQLITE3_TEXT);
            $stmt->bindValue(':categoria',trim($_POST['categoria']),SQLITE3_TEXT);
            $stmt->bindValue(':morada',trim($_POST['morada']),SQLITE3_TEXT);
            $stmt->bindValue(':ativo',(int)$_POST['ativo'],SQLITE3_INTEGER);
            $stmt->execute(); $mensagem='Restaurante criado. Agora pode associar um utilizador do tipo restaurante.';
        }
        if ($acao === 'atualizar_restaurante') {
            $stmt=$db->prepare("UPDATE restaurantes SET nome=:nome,categoria=:categoria,morada=:morada,ativo=:ativo WHERE id=:id");
            $stmt->bindValue(':nome',trim($_POST['nome']),SQLITE3_TEXT);
            $stmt->bindValue(':categoria',trim($_POST['categoria']),SQLITE3_TEXT);
            $stmt->bindValue(':morada',trim($_POST['morada']),SQLITE3_TEXT);
            $stmt->bindValue(':ativo',(int)$_POST['ativo'],SQLITE3_INTEGER);
            $stmt->bindValue(':id',(int)$_POST['id'],SQLITE3_INTEGER);
            $stmt->execute(); $mensagem='Restaurante atualizado.';
        }
        if ($acao === 'remover_restaurante') {
            $stmt=$db->prepare("DELETE FROM restaurantes WHERE id=:id");
            $stmt->bindValue(':id',(int)$_POST['id'],SQLITE3_INTEGER); $stmt->execute(); $mensagem='Restaurante removido.';
        }
        if ($acao === 'associar') {
            $restauranteId=(int)$_POST['restaurante_id']; $utilizadorId=(int)$_POST['utilizador_id'];
            if ($utilizadorId === 0) {
                $stmt=$db->prepare("UPDATE restaurantes SET utilizador_id=NULL WHERE id=:rid");
                $stmt->bindValue(':rid',$restauranteId,SQLITE3_INTEGER); $stmt->execute(); $mensagem='Associação removida.';
            } else {
                $tipo = $db->querySingle("SELECT tipo FROM utilizadores WHERE id=$utilizadorId", true);
                if (!$tipo || $tipo['tipo'] !== 'restaurante') { throw new Exception('Só é possível associar utilizadores do tipo restaurante.'); }
                $stmt=$db->prepare("UPDATE restaurantes SET utilizador_id=:uid WHERE id=:rid");
                $stmt->bindValue(':uid',$utilizadorId,SQLITE3_INTEGER); $stmt->bindValue(':rid',$restauranteId,SQLITE3_INTEGER); $stmt->execute(); $mensagem='Utilizador associado ao restaurante.';
            }
        }
    } catch (Exception $e) { $erro='Erro: '.$e->getMessage(); }
}
$utilizadores=$db->query("SELECT id,nome,username,email,tipo,ultimo_acesso FROM utilizadores ORDER BY id ASC");
$restaurantes=$db->query("SELECT r.*, u.nome AS dono_nome, u.username AS dono_username FROM restaurantes r LEFT JOIN utilizadores u ON u.id=r.utilizador_id ORDER BY r.nome ASC");
$restaurantesAssoc=$db->query("SELECT r.*, u.nome AS dono_nome FROM restaurantes r LEFT JOIN utilizadores u ON u.id=r.utilizador_id ORDER BY r.nome ASC");
$usersRest=$db->query("SELECT id,nome,username FROM utilizadores WHERE tipo='restaurante' ORDER BY nome ASC");
$encomendas=$db->query("SELECT e.id,e.data,e.estado,e.total,u.nome AS cliente,r.nome AS restaurante FROM encomendas e JOIN utilizadores u ON u.id=e.utilizador_id JOIN restaurantes r ON r.id=e.restaurante_id ORDER BY e.data DESC LIMIT 20");
$totalUsers=$db->querySingle("SELECT COUNT(*) FROM utilizadores");
$totalRest=$db->querySingle("SELECT COUNT(*) FROM restaurantes");
$totalEnc=$db->querySingle("SELECT COUNT(*) FROM encomendas");
?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Painel Admin | FoodToGo</title><link rel="stylesheet" href="styles/styles.css"></head>
<body class="pagina-painel"><header><h1>FoodToGo</h1><nav><a href="index.php">Home</a><a href="restaurantes.php">Restaurantes</a><span class="nav-utilizador">Admin: <?= h($_SESSION['nome']) ?></span><a href="scripts/logout.php" class="nav-destaque">Sair</a></nav></header>
<main><section class="painel-container"><div class="titulo-pagina"><h2>Painel Administrador</h2><p>Gestão da plataforma: utilizadores, restaurantes e associação entre ambos.</p></div>
<?php if($mensagem):?><div class="mensagem-ok"><?=h($mensagem)?></div><?php endif;?><?php if($erro):?><div class="mensagem-erro"><?=h($erro)?></div><?php endif;?>
<div class="painel-grid"><div class="card"><h3><?= (int)$totalUsers ?></h3><p>Utilizadores</p></div><div class="card"><h3><?= (int)$totalRest ?></h3><p>Restaurantes</p></div><div class="card"><h3><?= (int)$totalEnc ?></h3><p>Encomendas</p></div></div>
<div class="tabs"><button class="tab-btn active" data-tab="tab-utilizadores">1. Utilizadores</button><button class="tab-btn" data-tab="tab-restaurantes">2. Restaurantes</button><button class="tab-btn" data-tab="tab-associar">3. Associar utilizador</button><button class="tab-btn" data-tab="tab-encomendas">Encomendas</button></div>
<div id="tab-utilizadores" class="tab-content active"><h3>Gerir Utilizadores</h3><table class="tabela-admin"><thead><tr><th>ID</th><th>Nome</th><th>Username</th><th>Email</th><th>Tipo</th><th>Último acesso</th><th>Ações</th></tr></thead><tbody><?php while($u=$utilizadores->fetchArray(SQLITE3_ASSOC)): ?><tr><form method="POST"><td><?= (int)$u['id'] ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"></td><td><input name="nome" value="<?= h($u['nome']) ?>" required></td><td><input name="username" value="<?= h($u['username']) ?>" required></td><td><input type="email" name="email" value="<?= h($u['email']) ?>" required></td><td><select name="tipo"><option value="cliente" <?= $u['tipo']==='cliente'?'selected':'' ?>>Cliente</option><option value="restaurante" <?= $u['tipo']==='restaurante'?'selected':'' ?>>Restaurante</option><option value="admin" <?= $u['tipo']==='admin'?'selected':'' ?>>Admin</option></select></td><td><?= h($u['ultimo_acesso'] ?? 'Sem registo') ?></td><td class="acoes"><button class="btn-primary" name="acao" value="atualizar_utilizador">Guardar</button><button class="btn-secondary" name="acao" value="remover_utilizador" onclick="return confirm('Remover utilizador?')">Remover</button></td></form></tr><?php endwhile; ?></tbody></table></div>
<div id="tab-restaurantes" class="tab-content"><h3>Criar Restaurante</h3><form method="POST" class="form-inline"><input name="nome" placeholder="Nome" required><input name="categoria" placeholder="Categoria"><input name="morada" placeholder="Morada"><select name="ativo"><option value="1">Ativo</option><option value="0">Inativo</option></select><button class="btn-primary" name="acao" value="adicionar_restaurante">Adicionar</button></form><h3>Gerir Restaurantes</h3><table class="tabela-admin"><thead><tr><th>ID</th><th>Nome</th><th>Categoria</th><th>Morada</th><th>Estado</th><th>Utilizador associado</th><th>Ações</th></tr></thead><tbody><?php while($r=$restaurantes->fetchArray(SQLITE3_ASSOC)): ?><tr><form method="POST"><td><?= (int)$r['id'] ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"></td><td><input name="nome" value="<?= h($r['nome']) ?>" required></td><td><input name="categoria" value="<?= h($r['categoria']) ?>"></td><td><input name="morada" value="<?= h($r['morada']) ?>"></td><td><select name="ativo"><option value="1" <?= (int)$r['ativo']===1?'selected':'' ?>>Ativo</option><option value="0" <?= (int)$r['ativo']===0?'selected':'' ?>>Inativo</option></select></td><td><?= $r['dono_nome'] ? h($r['dono_nome']).' (@'.h($r['dono_username']).')' : 'Sem associação' ?></td><td class="acoes"><button class="btn-primary" name="acao" value="atualizar_restaurante">Guardar</button><button class="btn-secondary" name="acao" value="remover_restaurante" onclick="return confirm('Remover restaurante?')">Remover</button></td></form></tr><?php endwhile; ?></tbody></table></div>
<div id="tab-associar" class="tab-content"><h3>Associar utilizador a restaurante</h3><p>Primeiro crie o utilizador. Depois altere o seu tipo para <strong>restaurante</strong>. Por fim, associe esse utilizador ao restaurante criado.</p><table class="tabela-admin"><thead><tr><th>Restaurante</th><th>Atual</th><th>Novo utilizador</th><th>Ação</th></tr></thead><tbody><?php while($r=$restaurantesAssoc->fetchArray(SQLITE3_ASSOC)): ?><tr><form method="POST"><td><?= h($r['nome']) ?><input type="hidden" name="restaurante_id" value="<?= (int)$r['id'] ?>"></td><td><?= $r['dono_nome'] ? h($r['dono_nome']) : 'Sem utilizador' ?></td><td><select name="utilizador_id"><option value="0">Sem associação</option><?php $usersRest->reset(); while($ur=$usersRest->fetchArray(SQLITE3_ASSOC)): ?><option value="<?= (int)$ur['id'] ?>" <?= (int)$r['utilizador_id']===(int)$ur['id']?'selected':'' ?>><?= h($ur['nome']) ?> (@<?= h($ur['username']) ?>)</option><?php endwhile; ?></select></td><td><button class="btn-primary" name="acao" value="associar">Associar</button></td></form></tr><?php endwhile; ?></tbody></table></div>
<div id="tab-encomendas" class="tab-content"><h3>Últimas encomendas da plataforma</h3><table class="tabela-admin"><thead><tr><th>Nº</th><th>Cliente</th><th>Restaurante</th><th>Data</th><th>Estado</th><th>Total</th></tr></thead><tbody><?php $tem=false; while($e=$encomendas->fetchArray(SQLITE3_ASSOC)): $tem=true; ?><tr><td>#<?= (int)$e['id'] ?></td><td><?= h($e['cliente']) ?></td><td><?= h($e['restaurante']) ?></td><td><?= h($e['data']) ?></td><td><?= h($e['estado']) ?></td><td><?= number_format((float)$e['total'],2,',','.') ?> €</td></tr><?php endwhile; if(!$tem): ?><tr><td colspan="6">Sem encomendas.</td></tr><?php endif; ?></tbody></table></div>
</section></main><footer><p>© 2026 FoodToGo</p></footer><script src="scripts/app.js"></script></body></html>
