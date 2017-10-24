<?php
require("../svg/header.php");
require("../db/connect.php");
require("../db/util.php");
//$areas = loadAreas($db,"process");
//$tags = loadTags($db);
require("../svg/body.php");
$filter_id = "";
if (isset($_GET["id"])){
	$filter_id = $_GET["id"];
}
//header('Content-Type: image/svg+xml'); //ne fonctionne pas car le type mime n'est pas reconnu

// Analyse des critères de recherche

// ****************************************************************
// Chercher tous les process correspondant aux critères de recherche
// ****************************************************************
$sql = <<<SQL
    SELECT process.id as id, process.name as process_name, process_step.id as step_id, process_step.name as step_name, step_type.name as step_type_name, process_step.sub_process_id
    FROM process
    INNER JOIN process_step ON process_step.process_id = process.id
    INNER JOIN step_type ON process_step.step_type_id = step_type.id
SQL;
// Appliquer les filtres
if ($filter_id != ""){
   $sql .= " WHERE process.id=".$filter_id;
}
$sql.= " ORDER BY id";
if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}

$areas = array();
$area = new stdClass();
$area->id        = 0;
$area->code      = "";
$area->parent_id = null;
$area->display   = "horizontal";
$area->elements  = array();
$area->subareas  = array();
$area->needed    = true;
$area->parent    = null;
$areas[] = $area;
$steps = array();
// Charger toutes les étapes
while($row = $result->fetch_assoc()){
	$process_name = $row["process_name"];
	$area->name     	= "Process ".$process_name;
	$step 				= new stdClass();
	$step->id 			= $row["step_id"];
	$step->type 		= "step";
	$step->type_name 	= $row["step_type_name"];
	$step->class 		= "process_".strtolower($step->type_name);
	$step->name 		= $row["step_name"];
	$step->links 		= array();
	$step->allreadyComputed = false;// flag qui sera positionné lorsqu'on aura calculé sa position, cela évite de boucler
	$steps[$step->id]	= $step;
	$area->elements[] = $step;
}
$result->free();
$sql = <<<SQL
    SELECT step_link.*
    FROM step_link
SQL;
// Appliquer les filtres
if ($filter_id != ""){
   $sql .= " WHERE process_id=".$filter_id;
}
$sql.= " ORDER BY process_id";
if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}
// Charger tous les liens et associer chaque step
while($row = $result->fetch_assoc()){
	$from_id 	= $row["from_step_id"];
	$to_id 		= $row["to_step_id"];
	$from_step 	= $steps[$from_id];
	$to_step	= $steps[$to_id];
	$link 		= new stdClass();
	$link->to 	= $to_step;
	$link->label	= $row["label"];
	$from_step->links[] = $link;
}
$result->free();
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
require("../db/disconnect.php");
require("../svg/footer.php");
?>
