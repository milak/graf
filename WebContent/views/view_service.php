<?php
require("../svg/header.php");
require("../db/connect.php");
require("../db/util.php");
require("../svg/body.php");

// ****************************************************************
// Chercher tous les noeuds correspondant aux critères de recherche
// ****************************************************************
$sql = <<<SQL
    SELECT service.*, instance.id as instance_id, instance.name as instance_name, environment.id as environment_id, environment.name as environment_name, environment.code as environment_code from service
    LEFT OUTER JOIN instance ON service.id = instance.service_id
    LEFT OUTER JOIN environment ON instance.environment_id = environment.id
SQL;
$showProcess = false;

if (isset($_GET["id"])){
	$service_id = $_GET["id"];
} else {
	displayErrorAndDie('Missing id argument');
}
$sql .= " where service.id = ".$service_id;
$sql .= " ORDER by service.id";
if (!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}

$areas = array();
$area = new stdClass();
$area->id        = 0;
$area->code      = "";
$area->name      = "Service";
$area->parent_id = null;
$area->display   = "vertical";
$area->elements  = array();
$area->subareas  = array();
$area->needed    = true;
$area->parent    = null;
$areas[] = $area;

$area_composants = new stdClass();
$area_composants->id        = 0;
$area_composants->code      = "";
$area_composants->name      = "Composants";
$area_composants->parent_id = null;
$area_composants->display   = "horizontal";
$area_composants->elements  = array();
$area_composants->subareas  = array();
$area_composants->needed    = true;
$area_composants->parent    = $area;
$area->subareas[] = $area_composants;

$area_ressources = new stdClass();
$area_ressources->id        = 0;
$area_ressources->code      = "";
$area_ressources->name      = "Autres ressources";
$area_ressources->parent_id = null;
$area_ressources->display   = "horizontal";
$area_ressources->elements  = array();
$area_ressources->subareas  = array();
$area_ressources->needed    = true;
$area_ressources->parent    = $area;


$area_instances = new stdClass();
$area_instances->id        = 0;
$area_instances->code      = "";
$area_instances->name      = "Instances";
$area_instances->parent_id = null;
$area_instances->display   = "horizontal";
$area_instances->elements  = array();
$area_instances->subareas  = array();
$area_instances->needed    = true;
$area_instances->parent    = $area;


while($row = $result->fetch_assoc()){
	$area->name = "Service ".$row["name"];
	if (isset($row["instance_id"])){
		$instance 				= new stdClass();
		$instance->id 			= $row["instance_id"];
		$instance->type 		= "instance";
		$instance->class 		= "service_instance";
		$instance->name 		= $row["instance_name"];
		$instance->links 		= array();
		$area_instances->elements[] = $instance;
	}
}

$result->free();
// Charger tous les composants liés au service
$sql = <<<SQL
    SELECT component.*, data.name as data_name, service.name as service_name, device.name as device_name, software.name as software_name from component
    INNER JOIN service_needs_component ON service_needs_component.component_id = component.id
	LEFT OUTER JOIN data 		ON component.data_id 		= data.id
	LEFT OUTER JOIN service 	ON component.service_id 	= service.id
	LEFT OUTER JOIN device 		ON component.device_id 		= device.id
	LEFT OUTER JOIN software	ON component.software_id 	= software.id
	where service_needs_component.service_id = $service_id
SQL;
if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}
$components = array();

while($row = $result->fetch_assoc()){
	$type = $row["type"];
	if ($type == "device"){
		$name = $row["device_name"];
	} else if ($type == "service"){
		$name = $row["service_name"];
	} else if ($type == "software"){
		$name = $row["software_name"];
	} else if ($type == "data"){
		$name = $row["data_name"];
	}
	$component 				= new stdClass();
	$component->linked		= 0;
	$component->id 			= $row["id"];
	$component->type 		= "component";
	$component->subtype 	= $type;
	$component->class 		= "component_".$type;
	$component->name 		= $name;
	$component->links 		= array();
	$component->allreadyComputed = false;// flag qui sera positionné lorsqu'on aura calculé sa position, cela évite de boucler
	$components[$component->id] = $component;
}
$result->free();
// Charger tous les liens
$sql = <<<SQL
	SELECT * FROM component_link
	WHERE service_id = $service_id
SQL;

if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}
while($row = $result->fetch_assoc()){
	$from_component = $components[$row["from_component_id"]];
	$to_component 	= $components[$row["to_component_id"]];
	$port = $row["port"];
	$protocole = $row["protocole"];
	$label = "";
	if ($protocole != ""){
		$label = $protocole;
	}
	if ($port != ""){
		$label .= "(".$port.")";
	}
	$to_component->linked++;
	$link 			= new stdClass();
	$link->to 		= $to_component;
	$link->label	= $label;
	$from_component->links[] = $link;
}
$result->free();
$componentsToPlace = array();
// Déplacer tous les composants non liés dans la partie ressources, conserver les composants liés pour calcul de leur position
foreach($components as $component){
	if ((count($component->links) > 0) || ($component->linked > 0)){
		$area_composants->elements[] = $component;
		$componentsToPlace[] = $component;
	} else {
		$area_ressources->elements[] = $component;
	}
}
if (count($area_ressources->elements) > 0){
	$area->subareas[] = $area_ressources;
}
if (count($area_instances->elements) > 0){
	$area->subareas[] = $area_instances;
}
// Positionner les composants reliés
$stack = array();
// -- recherche du premier composant
foreach($componentsToPlace as $component){
	if ($component->linked > 0) {
		continue;
	}
	$stack[] = $component;
	break;
}
// -- calculer le positionnement de chaque composant
$x = (AREA_GAP*2);
$y = (AREA_GAP*2)+20;
$maxheight = 0;
$antiInfiniteLoop = 20;
while (count($stack) > 0){
	$antiInfiniteLoop--;
	if ($antiInfiniteLoop == 0){
		displayErrorAndDie("Boucle infinie détectée");
	}
	$newstack = array();
	foreach($stack as $component){
		$component->x = $x;
		$component->y = $y;
		$component->width = ELEMENT_WIDTH;
		$component->height = ELEMENT_HEIGHT;
		$component->allreadyComputed = true;
		foreach($component->links as $link){
			if (!$link->to->allreadyComputed){
				$newstack[] = $link->to;
			}
		}
		$maxheight = max($maxheight,$y);
		$y += 150;
	}
	$stack = $newstack;
	$x += 250;
	$y = (AREA_GAP*2)+20;
}
$area_composants->x = AREA_GAP;
$area_composants->y = AREA_GAP+15;
$area_composants->width = $x+100;
$area_composants->height = $maxheight + AREA_GAP + 15;
$maxwidth = $area_composants->width;
// -- positionner la zone principale
$area->x = 0;
$area->y = 0;
// -- calculer le positionnement des autres zones
	$x = AREA_GAP;
	$y = $area_composants->y+$area_composants->height+AREA_GAP;
	$first = true;
	foreach ($area->subareas as $subarea){
		if ($first){
			$first = false;
			continue;
		}
		$subarea->x = $x;
		$subarea->y = $y;
		computeSize($subarea);
		$maxwidth = max($maxwidth,$subarea->width);
		$y += $subarea->height + AREA_GAP;
		//$x += $subarea->width + AREA_GAP;
	}
$area->height = $y;
$area->width = $maxwidth+(2*AREA_GAP);
// Afficher le résultat
displayArea(0,$area_composants);
displayArea(0,$area);
require("../db/disconnect.php");
require("../svg/footer.php");
?>
