<?php
if (!isset($_GET["itemId"])){
	displayErrorAndDie('Missing itemId argument');
	return;
}
require("../svg/header.php");
require("../dao/dao.php");
require("util.php");
$dao->connect();
$areas = $dao->getViewByName("technical");
require("../svg/body.php");
//header('Content-Type: image/svg+xml'); ne fonctionne pas car le type mime n'est pas reconnu
$devicesUsed = array();
$allReadyBrowsed = array();
function recursiveSearch($id){
	global $_allReadyBrowsed;
	global $dao;
	error_log('recursiveSearch'.$id);
	if (isset($_allReadyBrowsed[$id])){
		return array();
	}
	$result = array();
	$_allReadyBrowsed[$id] = true;
	$items = $dao->getRelatedItems($id,'*','down');
	foreach ($items as $item){
		if ($item->category->name == 'location'){
			$result[] = $item;
			break;
		} else if ($item->category->name == 'device'){
			$devicesUsed[] = $item;
		} else if ($item->class->name == 'Server'){
			$devicesUsed[] = $item;
		}
		foreach (recursiveSearch($item->id) as $foundItem){
			$result[] = $foundItem;
		}
	}
	return $result;
}

$items = recursiveSearch($_GET['itemId']);

foreach ($dao->getItems((object)['id'=>$_GET['itemId']]) as $currentItem){
	if ($currentItem->category->name == 'location'){
		$items[] = $currentItem;
	} else if ($currentItem->category->name == 'device'){
		$devicesUsed[] = $currentItem;
	}
	break;
}
// Pour chaque localisation trouvée
foreach ($items as $item){
	if (isset($areas[$item->name])){
		$area 					= $areas[$item->name];
		$area->elements[] 		= $item;
		$item->type 			= $item->category->name;
		$item->display 			= new stdClass();
		$item->display->class 	= 'rect_100_100';//$item->category->name;
		error_log($item->category->name);
		$area->setNeeded(true);
		$item->subElements 		= array();
		foreach ($dao->getRelatedItems($item->id,'*','up') as $overitem){
			$item->subElements[] = $overitem;
		}
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