<?php
if (!isset($_GET["id"])){
	return;
}
require("../svg/header.php");
require("../dao/dao.php");
require("util.php");
$dao->connect();
$areas = $dao->getViewByName("technical");
require("../svg/body.php");
//header('Content-Type: image/svg+xml'); ne fonctionne pas car le type mime n'est pas reconnu

$items = recursiveSearch($_GET['id'],array('location'),'down');

$itemById = $dao->getItems((object)['id'=>$_GET['id']]);
foreach ($itemById as $item){
	$itemById = $item;
	$items[] = $item;
	break;
}
foreach ($items as $item){
	if (isset($areas[$item->name])){
		$area = $areas[$item->name];
		$area->elements[] = $item;
		$item->type = $itemById->category->name;
		$item->display->class = $itemById->category->name;
		error_log($itemById->category->name);
		$area->setNeeded(true);
	}
}
// Conserver uniquement les zones racines nécessaires
$roots = array();
foreach ($areas as $area){
	if (!$area->needed){
		continue;
	}
	if ($area->parent != null){
		continue;
	}
	$roots[] = $area;
}
// Afficher le résultat
display($roots);
$dao->disconnect();
require("../svg/footer.php");
?>