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
	$sql .= " where service.id = ".$_GET['id'];
} else {
	displayErrorAndDie('Missing id argument');
}
$sql .= " ORDER by service.id";
if(!$result = $db->query($sql)){
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
$area->subareas[] = $area_instances;

while($row = $result->fetch_assoc()){
	if (isset($row["instance_id"])){
		$instance 				= new stdClass();
		$instance->id 			= $row["instance_id"];
		$instance->type 		= "instance";
		$instance->class 		= "rect_200_80";
		$instance->name 		= $row["instance_name"];
		$instance->links 		= array();
		$area_instances->elements[] = $instance;
	}
}

$result->free();

// Afficher le résultat
display($areas);
require("../db/disconnect.php");
require("../svg/footer.php");
?>
