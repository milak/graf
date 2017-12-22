<?php
//header('Content-Type: image/svg+xml'); //ne fonctionne pas car le type mime n'est pas reconnu
require("../svg/header.php");
require("../dao/dao.php");
$dao->connect();
$areas = $dao->getViewByName("metier");
require("../svg/body.php");
if (!isset($_GET["id"])){
    displayErrorAndDie('Need domain "id" argument');
} else {
    $domain_id = $_GET["id"];
}
$domain = $dao->getDomainById($domain_id);
// Chercher tous les process du domaine
$businessProcesses = $dao->getBusinessProcessByDomainId($domain_id);
$rootarea = $areas["root"];
$rootarea->needed = true;
// Chargement des processus
$actors = array();
$process = array();
$services = array();

$processarea = $areas["process"];
$servicearea = $areas["service"];
$actorarea = $areas["actor"];
$resourcesarea = $areas["resource"];
foreach ($businessProcesses as $businessProcess){
    if ($processarea != null){
        $obj = new stdClass();
        $obj->id 		= $businessProcess->id;
        $obj->type 		= "process";
        $obj->class 	= "process_".strtolower("sub-process");
        $obj->name 		= $businessProcess->name;
        $obj->links 	= array();
        $processarea->elements[] = $obj;
        $processarea->needed = true;
    }
    $steps = $dao->getBusinessProcessStructure($businessProcess->id);
    foreach ($steps as $step){
        $type_name 	= $step->type_name;
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
            if ($processarea != null){
                $obj = new stdClass();
                $obj->id 		= $row["sub_process_id"];
                $obj->type 		= "process";
                $obj->class 	= "process_".strtolower($type_name);
                $obj->name 		= $row["step_name"];
                $obj->links 	= array();
                $processarea->elements[] = $obj;
                $processarea->needed = true;
            }
        }
    }
}
if ($servicearea != null){
    // Chargement des services
    $services = $dao->getServicesByDomainId($domain_id);
    // Charger tous les services
    foreach($services as $service){
    	$obj = new stdClass();
    	$obj->id 		= $service->id;
    	$obj->type 		= "service";
    	$obj->class 	= "process_service";
    	$obj->name 		= $service->name;
    	$obj->links 	= array();
    	$servicearea->elements[] = $obj;
    	$servicearea->needed = true;
    }
}

/*$sql = <<<SQL
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
$result->free();*/
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
$dao->disconnect();
require("../svg/footer.php");
?>