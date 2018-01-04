<?php
if (isset($_GET["id"])){
    $id = $_GET["id"];
} else {
    die("Missing id argument");
}
require("../dao/dao.php");
require("util.php");
$dao->connect();
$process = $dao->getItemById($id);
$steps = (new Process($dao->getItemStructure($id)))->elements;
require("../svg/header.php");
require("../svg/body.php");

//header('Content-Type: image/svg+xml'); //ne fonctionne pas car le type mime n'est pas reconnu

$areas = array();
$area = new stdClass();
$area->id        = 0;
// TODO retrouver le nom du process
$area->name     	= "Processus ".$process->name;
$area->code      = "";
$area->parent_id = null;
$area->display   = "horizontal";
$area->elements  = array();
$area->subareas  = array();
$area->needed    = true;
$area->parent    = null;
$areas[] = $area;

// Charger toutes les étapes
foreach($steps as $step){
	$step->type 		= "step";
	$step->class 		= "process_".strtolower($step->type_name);
	$step->allreadyComputed = false;// flag qui sera positionné lorsqu'on aura calculé sa position, cela évite de boucler
	$area->elements[] = $step;
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