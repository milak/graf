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
$start_step = null;
// Charger toutes les étapes
while($row = $result->fetch_assoc()){
	$process_name = $row["process_name"];
	$area->name      = "Process ".$process_name;
	$step 			= new stdClass();
	$step->id 		= $row["step_id"];
	$step->type 		= "step";
	$step->type_name 	= $row["step_type_name"];
	if ($step->type_name == "START"){
		// verifier que l'on a plus d'un start
		if ($start_step != null){
			displayErrorAndDie("Il y a plus d'une étape start");
		}
		$start_step = $step;
	}
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
// Calcul des coordonnées
$maxy = 0;
$maxx = 0;
$x = AREA_GAP;
$y = AREA_GAP + 10;
if ($start_step == null){
	displayErrorAndDie("Pas d'étape de démarrage");
}
// J'empile l'étape start dans la pile
$waitstack = array();
$stack = array();
$set = new stdClass();
$set->step = $start_step;
$set->y = $y;
$stack[] = $set;
$antiInfiniteLoop = 20;
while (count($stack) > 0){
	$antiInfiniteLoop--;
	if ($antiInfiniteLoop == 0){
		displayErrorAndDie("Boucle infinie détectée");
	}
	// voir s'il ne faut pas basculer des éléments de la pile dans la pile d'attente
	$newStack = array();
	foreach ($stack as $set){
		$waited = false;
		foreach($steps as $step){
			if ($step->allreadyComputed) {
				continue;
			}
			foreach($step->links as $link){
				if ($link->to == $set->step){
					$waitstack[] = $set;
					$waited = true;
					break 2; // sortir de la boucle sur les étapes
				}
			}
		}
		if ($waited == false){
			$newStack[] = $set;
		}
	}
	$stack = $newStack;
	// si écarter les éléments en attente abouttit à tout vider, tant pis, on rebascule un des éléments
	if (count($stack) == 0){
		$set = $waitstack[0];
		unset($waitstack[0]);
		// indiquer à tous ceux qui pointent dessus et qui ne sont pas calculés qu'il s'agit d'un lien arrière
		foreach($steps as $step){
			if ($step->allreadyComputed) {
				continue;
			}
			/*TODO : voir comment faire pour traiter ça dans body.php
			foreach($step->links as $link){
				if ($link->to == $set->step){
					$link->backlink = true;
				}
			}*/
		}
		$stack[] = $set;
	}
	$newStack = array();
	// Pour chaque element dans la pile
	foreach ($stack as $set){
		$step = $set->step;
		if ($step->allreadyComputed) {
			continue;
		}
		$y = max($y,$set->y);
		$step->x = $x;
		$step->y = $y;
		$step->width = ELEMENT_WIDTH;
		$step->height = ELEMENT_HEIGHT;
		$step->allreadyComputed = true;
		foreach ($step->links as $link){
			$to_step = $link->to;
			if (!$to_step->allreadyComputed){
				$newSet = new stdClass();
				$newSet->step = $to_step;
				$newSet->y = $y;
				$newStack[] = $newSet;
			}
		}
		$y += AREA_GAP + ELEMENT_HEIGHT + 50;
		$maxy = max($maxy,$y);
	}
	$stack = $newStack;
	$x += AREA_GAP + ELEMENT_WIDTH + 100;
	$y = AREA_GAP + 10;
	// On rebascule les éléments en attente dans la pile
	foreach($waitstack as $set){
		$stack[] = $set;
	}
	$waitstack = array();
}
$maxx = $x;
$y+=$maxy+AREA_GAP;
$x = AREA_GAP;
// Mettre en ligne en bas tous les 
$notplaced = false;
foreach ($steps as $step){
	if (!$step->allreadyComputed){
		$notplaced = true;
		error_log("Step not computed ".$step->name);
		$step->x = $x;
		$step->y = $y;
		$step->width = ELEMENT_WIDTH;
		$step->height = ELEMENT_HEIGHT;
		$step->allreadyComputed = true;
		$x += AREA_GAP + ELEMENT_WIDTH;
	}
}
if ($notplaced){
	$y += ELEMENT_HEIGHT + AREA_GAP;
}
$area->width = max($maxx,$x);
$area->height = $y;
$area->x = 0;
$area->y = 0;
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
displayArea(0,$roots[0]);
require("../db/disconnect.php");
require("../svg/footer.php");
?>
