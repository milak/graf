<?php
if (!isset($_GET['id'])){
    die("Missing id argument");
}
require ("../dao/dao.php");
require ("util.php");
$dao->connect();
$id = $_GET['id'];
$items=$dao->getItems((object)['id'=>$id]);
if (count($items) == 0){
    die("Item not found");
}
$item = $items[0];
$category = $item->category->name;
$domains = array();
$actors = array();
$datas = array();
$overitems = $dao->getRelatedItems($id,"*","up");
foreach($overitems as $overitem){
    if ($overitem->category->name == "domain"){
        $domains[] = $overitem;
    } else if ($overitem->category->name == "actor"){
        $actors[] = $overitem;
    } else {
        
    }
}

$subitems = $dao->getRelatedItems($id,"*","down");
$environnements = array();
$nameLength = strlen($item->name);
foreach($subitems as $subitem){
    if ($subitem->category->name == "solution"){
        $env = new stdClass();
        $env->id   = $subitem->id;
        $env->name = substr($subitem->name,$nameLength+1);
        $env->item = $subitem;
        $environnements[] = $env;
    } else if ($subitem->category->name == "data"){
        $datas[] = $subitem;
    }
}
?>
<html>
<header>
<script type="text/javascript" src="../lib/jquery/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="../lib/svgtool/svg-pan-zoom.js"></script>
<style type="text/css">
body {
    margin       : 50px;
    padding      : 10px;
    border-style : outset;
    border-width : 4px;
}
/*h1 {
    counter-reset: h2counter;
}*/
h1:before {
    content: counter(h1counter,upper-roman) ".\0000a0\0000a0";
    counter-increment: h1counter;
    counter-reset: h2counter;
}
h2:before {
    content: counter(h2counter) ".\0000a0\0000a0";
    counter-increment: h2counter;
    counter-reset: h3counter;
}
h3:before {
    content: counter(h2counter) "." counter(h3counter) ".\0000a0\0000a0";
    counter-increment: h3counter;
}
a:VISITED{
    color: blue;
}
table {
    border-collapse:collapse;
}
tr, td {
    border:1px solid black;
}
th {
    background-color: lightgray;
}
td {
    text-align:left;
    padding : 5px;
}
caption {
    font-weight:bold;
}
</style>
</header>
<body>
<div style="height:110%;width:100%">
	<div style="height:35%;width:100%"></div>
	<div style="width:100%;text-align:center;font-size: 40px">
		Dossier d'architecture<br/>
		<?php echo $item->name;?>
	</div>
</div>
<div style="height:110%;width:100%">
<div style="width:100%;text-align: center;font-size:25px">Sommaire</div>
<br/>
<br/>
<br/>
<ol style="list-style-type:upper-roman;">
	<li><a href="#contexte">Contexte</a></li>
	<li><a href="#architecture_logique">Architecture logique</a></li>
	<ol>
		<li><a href="#schema_global">Schéma global</a></li>
		<li><a href="#donnees">Données</a></li>
	</ol>
	<li><a href="#environnements">Environnements</a></li>
	<ol>
	<?php
	$index = 1;
	foreach ($environnements as $environnement){
	    echo '<li>';
	    echo '<a href="#environnement_'.$index.'">'.$environnement->name.'</a>';
	    echo '</li>';
	    $index++;
	}
	?>
	</ol>
</ol>
</div>

<h1><a name="contexte">Contexte</a></h1>

<table>
<thead>
	<tr><th>Description</th><th></th></tr>
</thead>
<tbody>
	<tr><td>Domaines</td><td>
<?php
    $first = true;
    foreach ($domains as $item){
        if (!$first){
            echo ', ';
        }
        echo $item->name;
        $first = false;
    }
?>
	</td></tr>
	<tr><td>Acteurs</td><td>
<?php
    $first = true;
    foreach ($actors as $item){
        if (!$first){
            echo ', ';
        }
        echo $item->name;
        $first = false;
    }
    $frames = array();
    $frame = new stdClass();
    $frame->name = 'frame_architecture_logique';
    $frame->url = './view_logique.php?id='.$id;
    $frames[] = $frame;
?>
	</td></tr>
</tbody>
</table>
<h1><a name="architecture_logique">Architecte logique</a></h1>
<h2><a name="schema_global">Schéma global</a></h2>
<svg id="frame_architecture_logique" style="width:100%;height:100%"></svg>
<h2><a name="donnees">Données</a></h2>
<ul>
<?php
foreach ($datas as $data){
    echo '<li>';
    echo $data->name;
    echo '</li>';
}
?>
</ul>
<h1><a name="environnements">Environnements</a></h1>
<?php
    $index = 1;
	foreach ($environnements as $environnement){
	    echo '<h2><a name="environnement_'.$index.'">'.$environnement->name.'</a></h2>';
	    echo '<svg id="frame_environnement_'.$index.'" style="width:100%;height:100%"></svg>';
	    $frame = new stdClass();
	    $frame->name = 'frame_environnement_'.$index;
	    $frame->url = './view_logique.php?id='.$environnement->id;
	    $frames[] = $frame;?>
<table style="width:90%">
<thead>
	<tr><th>Composant</th><th>Classe</th><th>Nom</th></tr>
</thead>
<tbody><?php
        $subitems = $dao->getRelatedItems($environnement->id,"*","down");
        foreach ($subitems as $subitem){
            echo '<tr><td>'.$subitem->category->name.'</td><td>'.$subitem->class->name.'</td><td>'.$subitem->name.'</td></tr>';
        }
?></tbody>
</table>
<?php	$index++;
	}?>
<script type="text/javascript">
$( function() {
<?php foreach ($frames as $frame){ ?>
	$.get( "<?php echo $frame->url; ?>", function( data ) {
		$('#<?php echo $frame->name; ?>').html(data);
		panZoomInstance = svgPanZoom("#<?php echo $frame->name; ?>", {
			panEnabled				: false,
		    zoomEnabled				: false,
		    dblClickZoomEnabled		: false,
		    controlIconsEnabled		: false,
		    fit						: true,
		    center					: false,
		    minZoom					: 0.1,
		    zoomScaleSensitivity 	: 0.3
		});
	});
<?php }?>
});
</script>
</body>
</html>
<?php
$dao->disconnect();
?>