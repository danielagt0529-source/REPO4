<?php
declare(strict_types=1);
session_start();
require __DIR__.'/config.php';
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function go(string $msg, string $type='ok'): never { $_SESSION['flash']=['msg'=>$msg,'type'=>$type]; header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) go('La sesión expiró. Inténtalo de nuevo.','err');
    $action=(string)($_POST['action']??'');
    if ($action==='delete') {
        $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
        if ($id) { $s=$pdo->prepare('DELETE FROM articulos WHERE id=?'); $s->execute([$id]); go('Artículo eliminado.'); }
        go('No se identificó el artículo.','err');
    }
    if (in_array($action,['create','update'],true)) {
        $nombre=trim((string)($_POST['nombre']??'')); $marca=trim((string)($_POST['marca']??''));
        $ubicacion=trim((string)($_POST['ubicacion']??'')); $unidades=filter_var($_POST['unidades']??null,FILTER_VALIDATE_INT);
        $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
        if ($nombre==='' || mb_strlen($nombre)>180 || mb_strlen($marca)>120 || mb_strlen($ubicacion)>180) go('El nombre es obligatorio. Revisa los campos.','err');
        if ($unidades===false || $unidades<0) go('Las unidades deben ser un entero igual o mayor que cero.','err');
        if ($action==='create') { $s=$pdo->prepare('INSERT INTO articulos(nombre,marca,unidades,ubicacion) VALUES(?,?,?,?)'); $s->execute([$nombre,$marca,$unidades,$ubicacion]); go('¡Artículo agregado al inventario!'); }
        if ($id) { $s=$pdo->prepare('UPDATE articulos SET nombre=?,marca=?,unidades=?,ubicacion=? WHERE id=?'); $s->execute([$nombre,$marca,$unidades,$ubicacion,$id]); go('Artículo actualizado correctamente.'); }
        go('No se pudo guardar el artículo.','err');
    }
}
$edit=null;
if (isset($_GET['editar']) && ctype_digit((string)$_GET['editar'])) { $s=$pdo->prepare('SELECT * FROM articulos WHERE id=?'); $s->execute([(int)$_GET['editar']]); $edit=$s->fetch()?:null; }
$q=trim((string)($_GET['q']??'')); $loc=trim((string)($_GET['ubicacion']??''));
$sql='SELECT * FROM articulos WHERE 1=1'; $params=[];
if ($q!=='') { $sql.=' AND (nombre LIKE ? OR marca LIKE ? OR ubicacion LIKE ?)'; $t='%'.$q.'%'; array_push($params,$t,$t,$t); }
if ($loc!=='') { $sql.=' AND ubicacion=?'; $params[]=$loc; }
$sql.=' ORDER BY nombre,id DESC'; $s=$pdo->prepare($sql); $s->execute($params); $items=$s->fetchAll();
$stats=$pdo->query("SELECT COUNT(*) total, COALESCE(SUM(unidades),0) unidades, COUNT(DISTINCT NULLIF(marca,'')) marcas, COUNT(DISTINCT NULLIF(ubicacion,'')) ubicaciones FROM articulos")->fetch();
$locations=$pdo->query("SELECT DISTINCT ubicacion FROM articulos WHERE ubicacion<>'' ORDER BY ubicacion")->fetchAll(PDO::FETCH_COLUMN);
$flash=$_SESSION['flash']??null; unset($_SESSION['flash']);
$f=$edit?:['id'=>'','nombre'=>'','marca'=>'','unidades'=>0,'ubicacion'=>''];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#c7f3f6"><title>AquaStock · Inventario</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="styles.css"></head><body>
<div class="backdrop-art"><div class="orb"></div><div class="cloud c1"></div><div class="cloud c2"></div><div class="hill"></div></div>
<div class="layout"><aside class="sidebar glass"><a class="brand" href="index.php"><span class="logo">✿</span><span>Aqua<span class="aqua">Stock</span><small>INVENTARIO NATURAL</small></span></a><p class="side-label">TU ESPACIO</p><a class="nav active" href="index.php">▦ &nbsp; Inventario</a><div class="side-note"><span>✧</span><div><b>Todo en su lugar</b><p>Un inventario fresco y organizado.</p></div></div><div class="user"><div class="avatar">AS</div><div><b>AquaStock</b><small>Panel de artículos</small></div><i></i></div></aside>
<main class="main"><header class="topbar"><div><span class="eyebrow">● &nbsp; INVENTARIO · ORGANIZACIÓN INTELIGENTE</span><h1>Un espacio para cada cosa <em>✦</em></h1><p>Todo lo que tienes, a la vista y en armonía.</p></div><div class="date">☀ Todo fluye <span>◷ <?=h(date('d/m/Y'))?></span></div></header>
<?php if($flash):?><div class="flash <?=h($flash['type'])?>"><?=h($flash['msg'])?><button onclick="this.parentElement.remove()">×</button></div><?php endif;?>
<section class="hero glass"><div><span class="eyebrow">HOLA, QUÉ BUENO TENERTE AQUÍ</span><h2>Un inventario en <em>equilibrio.</em></h2><p>Registra tus artículos, conoce tus existencias y encuentra cada cosa sin complicaciones.</p><button class="btn" onclick="openForm()">＋ Agregar artículo</button></div><div class="hero-art"><div class="sun"></div><div class="ring"></div><div class="box">✿</div><div class="leaf"></div><div class="bubble"></div></div></section>
<section class="stats">
<div class="stat glass"><span class="ico violet">▤</span><div><small>REFERENCIAS</small><strong><?= (int)$stats['total']?></strong><p>Artículos registrados</p></div></div>
<div class="stat glass"><span class="ico aqua">▦</span><div><small>EXISTENCIAS</small><strong><?=number_format((int)$stats['unidades'],0,',','.')?></strong><p>Unidades en inventario</p></div></div>
<div class="stat glass"><span class="ico coral">✳</span><div><small>MARCAS</small><strong><?= (int)$stats['marcas']?></strong><p>Marcas diferentes</p></div></div>
<div class="stat glass"><span class="ico green">⌖</span><div><small>UBICACIONES</small><strong><?= (int)$stats['ubicaciones']?></strong><p>Espacios identificados</p></div></div></section>
<section class="inventory glass"><div class="section-head"><div><span class="eyebrow">TU COLECCIÓN</span><h2>Artículos <span class="count"><?=count($items)?></span></h2><p>Consulta y administra tus existencias.</p></div><button class="btn" onclick="openForm()">＋ Nuevo artículo</button></div>
<form class="filters" method="get"><label class="search">⌕ <input type="search" name="q" value="<?=h($q)?>" placeholder="Buscar nombre, marca o ubicación..."></label><select name="ubicacion"><option value="">Todas las ubicaciones</option><?php foreach($locations as $l):?><option value="<?=h($l)?>" <?=$loc===$l?'selected':''?>><?=h($l)?></option><?php endforeach;?></select><button class="filter" type="submit">Buscar</button><?php if($q!==''||$loc!==''):?><a class="clear" href="index.php">Limpiar</a><?php endif;?></form>
<?php if(!$items):?><div class="empty"><div>▤<i>✧</i></div><h3><?=$q!==''||$loc!==''?'No hay coincidencias':'Tu inventario empieza aquí'?></h3><p><?=$q!==''||$loc!==''?'Prueba otra búsqueda o cambia la ubicación.':'Agrega tu primer artículo para empezar a organizar tu espacio.'?></p><?php if($q===''&&$loc===''):?><button class="btn" onclick="openForm()">＋ Agregar primer artículo</button><?php endif;?></div>
<?php else:?><div class="table-wrap"><table><thead><tr><th>ARTÍCULO</th><th>MARCA</th><th>UNIDADES</th><th>UBICACIÓN</th><th></th></tr></thead><tbody><?php foreach($items as $i=>$a):$tones=['tone-a','tone-b','tone-c','tone-d'];?><tr><td><div class="item"><span class="item-icon <?=$tones[$i%4]?>"><?=h(mb_strtoupper(mb_substr($a['nombre'],0,1)))?></span><div><b><?=h($a['nombre'])?></b><small>ARTÍCULO #<?= (int)$a['id']?></small></div></div></td><td><?=$a['marca']!==''?'<span class="brand-pill">'.h($a['marca']).'</span>':'<span class="muted">—</span>'?></td><td><span class="units <?= (int)$a['unidades']===0?'zero':''?>">● &nbsp;<?=number_format((int)$a['unidades'],0,',','.')?></span></td><td><span class="location">⌖ &nbsp;<?=$a['ubicacion']!==''?h($a['ubicacion']):'<span class="muted">Sin ubicación</span>'?></span></td><td><div class="actions"><a class="action edit" href="index.php?editar=<?=(int)$a['id']?>" title="Editar">✎</a><form method="post" onsubmit="return confirm('¿Seguro que deseas eliminar este artículo?')"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$a['id']?>"><button class="action del" title="Eliminar">⌫</button></form></div></td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
<div class="panel-foot"><span>Mostrando <b><?=count($items)?></b> <?=count($items)===1?'artículo':'artículos'?></span><span>● Todo en orden, todo en su lugar</span></div></section><footer>© <?=date('Y')?> AquaStock <span>Un toque de naturaleza digital <b>✿</b></span></footer></main></div>
<div class="modal-bg <?=$edit?'visible':''?>" id="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle"><div class="modal glass"><div class="modal-head"><button class="close" onclick="closeForm()" type="button">×</button><div class="modal-logo">✿</div><span class="eyebrow"><?=$edit?'ACTUALIZA TU COLECCIÓN':'NUEVA REFERENCIA'?></span><h2 id="modalTitle"><?=$edit?'Editar artículo':'Agregar un artículo'?></h2><p>Unos cuantos detalles y quedará en su sitio.</p></div><form class="article-form" method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="<?=$edit?'update':'create'?>"><input type="hidden" name="id" value="<?=h($f['id'])?>"><label>Nombre del artículo <i>*</i><input name="nombre" maxlength="180" required value="<?=h($f['nombre'])?>" placeholder="Ej. Cuaderno ecológico"></label><label>Marca <small>OPCIONAL</small><input name="marca" maxlength="120" value="<?=h($f['marca'])?>" placeholder="Ej. Natura, Acme..."></label><div class="form-row"><label>Unidades <i>*</i><input type="number" name="unidades" min="0" step="1" required value="<?=h($f['unidades'])?>"></label><label>Ubicación <small>OPCIONAL</small><input name="ubicacion" maxlength="180" value="<?=h($f['ubicacion'])?>" placeholder="Ej. Estante A-2"></label></div><div class="form-actions"><button class="cancel" type="button" onclick="closeForm()">Cancelar</button><button class="btn" type="submit"><?=$edit?'Guardar cambios':'Guardar artículo'?> ↗</button></div></form></div></div>
<script>
const modal=document.getElementById('modal');
function openForm(){modal.classList.add('visible');document.body.classList.add('locked');const x=modal.querySelector('[name="nombre"]');if(x)x.focus()}
function closeForm(){modal.classList.remove('visible');document.body.classList.remove('locked');if(new URLSearchParams(location.search).has('editar'))location.href='index.php'}
modal.addEventListener('click',e=>{if(e.target===modal)closeForm()});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal.classList.contains('visible'))closeForm()});if(modal.classList.contains('visible'))document.body.classList.add('locked');
</script></body></html>