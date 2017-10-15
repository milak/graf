<?php
require("../svg/header.php");
require("../db/connect.php");
require("../db/util.php");
require("../svg/body.php");
$areas = loadAreas($db,"logique");
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

while($row = $result->fetch_assoc()){
	if (isset($row["instance_id"])){
		$instance 				= new stdClass();
		$instance->id 			= $row["instance_id"];
		$instance->type 		= "instance";
		$instance->class 		= "service_instance";
		$instance->name 		= $row["instance_name"];
		$instance->links 		= array();
		$area->elements[] 		= $instance;
	}
}
$result->free();


function loadComponents($db,$sql,$areas){
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
		$component->service_id  = $row["service_id"];
		$component->allreadyComputed = false;// flag qui sera positionné lorsqu'on aura calculé sa position, cela évite de boucler
		$area_id 				= $row["area_id"];
		// Marquer la zone comme nécessaire
		$area = $areas[$area_id];
		$area->elements[] = $component;
		if (!$area->needed) {
			$area->needed = true; // indiquer qu'elle est nécessaire ainsi que sa zone parente et récursivement
			$parent = $area->parent;
			while ($parent != null){
				$parent->needed=true;
				$parent = $parent->parent;
			}
		}
		$components[$component->id] = $component;
	}
	$result->free();
	return $components;
}

// Charger tous les composants liés au service
$sql = <<<SQL
    SELECT component.*, data.name as data_name, service.name as service_name, device.name as device_name, software.name as software_name, service_needs_component.service_id as service_id
	from component
    INNER JOIN service_needs_component ON service_needs_component.component_id = component.id
	LEFT OUTER JOIN data 		ON component.data_id 		= data.id
	LEFT OUTER JOIN service 	ON component.service_id 	= service.id
	LEFT OUTER JOIN device 		ON component.device_id 		= device.id
	LEFT OUTER JOIN software	ON component.software_id 	= software.id
	where service_needs_component.service_id = $service_id
SQL;
$components = loadComponents($db,$sql,$areas);

// Charger tous les composants d'autres services pour lesquels un des composants du service est lié
$sql = <<<SQL
SELECT component_externe.*, data.name as data_name, service.name as service_name, device.name as device_name, software.name as software_name, service_needs_component_externe.service_id as service_id
	FROM component component_externe
    INNER JOIN service_needs_component as service_needs_component_externe ON service_needs_component_externe.component_id = component_externe.id
    INNER JOIN component_link ON component_link.to_component_id = component_externe.id
	LEFT OUTER JOIN data 		ON component_externe.data_id 		= data.id
	LEFT OUTER JOIN service 	ON component_externe.service_id 	= service.id
	LEFT OUTER JOIN device 		ON component_externe.device_id 		= device.id
	LEFT OUTER JOIN software	ON component_externe.software_id 	= software.id, component component_interne
    INNER JOIN service_needs_component as service_needs_component_interne ON service_needs_component_interne.component_id = component_interne.id
    where 	service_needs_component_externe.service_id != $service_id
	and 	service_needs_component_interne.service_id = $service_id
    and  	component_link.from_component_id = component_interne.id
SQL;

$externalComponents = loadComponents($db,$sql,$areas);

foreach($externalComponents as $component){
	$component->type 		= "external_component";
	$components[$component->id] = $component;
}

// Charger tous les liens pour lesquels le composant "from" est nécessaire au service (le to peut-être issu d'un autre service)
$sql = <<<SQL
	SELECT * FROM component_link
	INNER JOIN component ON component.id = component_link.from_component_id
	INNER JOIN service_needs_component ON service_needs_component.component_id = component.id
	WHERE service_needs_component.service_id = $service_id
SQL;

if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}
while($row = $result->fetch_assoc()){
	$from_component_id 	= $row["from_component_id"];
	$from_component 	= $components[$from_component_id];
	$to_component_id 	= $row["to_component_id"];
	$to_component 		= $components[$to_component_id];
	$port 				= $row["port"];
	$protocole 			= $row["protocole"];
	$label = "";
	if ($protocole != ""){
		$label 			= $protocole;
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
// Afficher le résultat
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
display($roots);

require("../db/disconnect.php");
require("../svg/footer.php");
?>
