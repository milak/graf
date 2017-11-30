<?php
//header('Content-Type: image/svg+xml'); //ne fonctionne pas car le type mime n'est pas reconnu
require("../svg/header.php");
require("../db/connect.php");
require("../db/util.php");
require("../svg/body.php");
if (!isset($_GET["id"])){
    displayErrorAndDie('Need domain "id" argument');
} else {
    $domain_id = $_GET["id"];
}
// Analyse des critères de recherche
$sql = <<<SQL
   SELECT * from domain
   WHERE id = $domain_id
SQL;
if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}
while($row = $result->fetch_assoc()){
	$domain_name = $row["name"];
}
$result->free();
// ****************************************************************
// Chercher tous les process correspondant aux critères de recherche
// ****************************************************************
$sql = <<<SQL
    SELECT process.id as id, process.name as process_name, process_step.id as step_id, process_step.name as step_name, step_type.name as step_type_name, process_step.sub_process_id, process_step.service_id
    FROM process
    LEFT OUTER JOIN process_step ON process_step.process_id = process.id
    LEFT OUTER JOIN step_type ON process_step.step_type_id = step_type.id
SQL;
// Appliquer les filtres
$sql .= " WHERE process.domain_id = '".$_GET["id"]."'";
$sql .= " ORDER BY process.id";
if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}

$areas = array();

$root_area = new stdClass();
$root_area->code 		= "";
$root_area->name 		= "Domaine ".$domain_name;
$root_area->parent 		= null;
$root_area->display 	= "vertical";
$root_area->elements 	= array();
$root_area->subareas 	= array();
$root_area->needed 		= true;
$areas[] = $root_area;

$area_ress = new stdClass();
$area_ress->code 	= "";
$area_ress->name 	= "Ressources";
$area_ress->display   	= "horizontal";
$area_ress->elements 	= array();
$area_ress->subareas 	= array();
$area_ress->needed 	= true;
$area_ress->parent 	= $root_area;
$root_area->subareas[] = $area_ress;

$area_actor = new stdClass();
$area_actor->code 	= "";
$area_actor->name 	= "Acteurs";
$area_actor->display   	= "grid";
$area_actor->elements 	= array();
$area_actor->subareas 	= array();
$area_actor->needed 	= true;
$area_actor->parent 	= $area_ress;
$area_ress->subareas[] = $area_actor;

$area_service = new stdClass();
$area_service->code 	= "";
$area_service->name 	= "Services";
$area_service->display  = "grid";
$area_service->elements = array();
$area_service->subareas = array();
$area_service->needed 	= true;
$area_service->parent 	= $area_ress;
$area_ress->subareas[] = $area_service;

$area_process = new stdClass();
$area_process->code      = "";
$area_process->name      = "Processus";
$area_process->display   = "vertical";
$area_process->elements  = array();
$area_process->subareas  = array();
$area_process->needed    = true;
$area_process->parent    = $root_area;

$root_area->subareas[] = $area_process;
// TODO garder un id de tous les composants listés pour éviter les doublons
$actors = array();
$process = array();
$services = array();
$current_process_id = -1;
// Charger toutes les processus
while($row = $result->fetch_assoc()){
	$id = $row["id"];
	if ($id != $current_process_id){
		$current_process_id = $id;
		$process_name = $row["process_name"];
		$obj = new stdClass();
		$obj->id 		= $row["id"];
		$obj->type 		= "process";
		$obj->class 		= "process_".strtolower("sub-process");
		$obj->name 		= $process_name;
		$obj->links 		= array();
		$area_process->elements[] = $obj;
		/*$process = new stdClass();
		$process->code      = "";
		$process->name      = "Process ".$process_name;
		$area->display   = "horizontal";
		$area->elements  = array();
		$area->subareas  = array();
		$area->needed    = true;
		$area->parent    = $area_process;
		//$area->elements[] = $step;
		$area_process->subareas[] = $area;*/
	}
	$type_name 	= $row["step_type_name"];
	if (($type_name == "START") || ($type_name == "END")){
		// SKIP
	} else if ($type_name == "ACTOR") {
		/*$obj = new stdClass();
		$obj->id 		= $row["step_id"];
		$obj->type 		= "box";
		$obj->class 	= "process_".strtolower($type_name);
		$obj->name 		= $row["step_name"];
		$obj->links 	= array();
		$area_actor->elements[] = $obj;*/
	} else if ($type_name == "SERVICE") {
	/*	$obj = new stdClass();
		$obj->id 		= $row["service_id"];
		$obj->type 		= "service";
		$obj->class 	= "process_".strtolower($type_name);
		$obj->name 		= $row["service_id"]."-".$row["step_name"];
		$obj->links 	= array();
		$area_service->elements[] = $obj;*/
	} else if ($type_name == "SUB-PROCESS") {
		$obj = new stdClass();
		$obj->id 		= $row["sub_process_id"];
		$obj->type 		= "process";
		$obj->class 	= "process_".strtolower($type_name);
		$obj->name 		= $row["step_name"];
		$obj->links 	= array();
		$area_process->elements[] = $obj;
	}
}
$result->free();

$sql = <<<SQL
   SELECT * from service
   WHERE domain_id = $domain_id
SQL;
if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}
// Charger tous les services
while($row = $result->fetch_assoc()){
	$obj = new stdClass();
	$obj->id 		= $row["id"];
	$obj->type 		= "service";
	$obj->class 	= "process_service";
	$obj->name 		= $row["name"];
	$obj->links 	= array();
	$area_service->elements[] = $obj;
}
$result->free();

$sql = <<<SQL
   SELECT element.* from element
   INNER JOIN element_class ON element.element_class_id = element_class.id
   INNER JOIN element_category ON element_class.element_category_id = element_category.id
   WHERE domain_id = $domain_id
	AND element_category.name = 'actor'
SQL;
if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}
// Charger tous les acteurs
while($row = $result->fetch_assoc()){
	$obj = new stdClass();
	$obj->id 		= $row["id"];
	$obj->type 		= "actor";
	$obj->class 	= "process_actor";
	$obj->name 		= $row["name"];
	$obj->links 	= array();
	$area_actor->elements[] = $obj;
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
if (count($roots) == 0){
	echo("<text x='50' y='50'>Aucun processus</text>");
} else {
	display($roots);
}
require("../db/disconnect.php");
require("../svg/footer.php");
?>
