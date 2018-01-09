<?php
//header('Content-Type: image/svg+xml'); //ne fonctionne pas car le type mime n'est pas reconnu
require("../svg/header.php");
require("../dao/dao.php");
require("util.php");
require("../svg/body.php");
if (!isset($_GET["id"])){
    displayErrorAndDie('Need domain "id" argument');
} else {
    $domain_id = $_GET["id"];
}
$dao->connect();
$areas = $dao->getViewByName("business");
$domain = $dao->getDomainById($domain_id);
// Chercher tous les process du domaine
$businessProcesses = $dao->getBusinessProcessByDomainId($domain_id);
// Chargement des processus
$actors     = array();
$process    = array();
$services   = array();
$rootarea       = $areas["root"];
$rootarea->name = $rootarea->name." ".$domain->name;
$processarea    = $areas["process"];
$servicearea    = $areas["service"];
$actorarea      = $areas["actor"];
$resourcesarea  = $areas["resource"];
$dataarea       = $areas["data"];
$solutionarea   = $areas["solution"];
foreach ($businessProcesses as $businessProcess){
    if ($processarea != null){
        $obj = new stdClass();
        $obj->id 		= $businessProcess->id;
        $obj->type 		= "process";
        $obj->class 	= "process_".strtolower("sub-process");
        $obj->name 		= $businessProcess->name;
        $obj->links 	= array();
        $processarea->addElement($obj);
    }
    $steps = (new Process($dao->getItemStructure($businessProcess->id)))->elements;
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
                $obj->id 		= $step->id;
                $obj->type 		= "process";
                $obj->class 	= "process_".strtolower($type_name);
                $obj->name 		= $step->name;
                $obj->links 	= array();
                $processarea->addElement($obj);
            }
        }
    }
}
$items = $dao->getItemsByDomainId($domain_id);
foreach($items as $item){
    $obj = new stdClass();
    $obj->id 		= $item->id;
    $obj->type 		= "item";
    $obj->name 		= $item->name;
    $obj->links 	= array();
    if ($item->category->name == "actor") {
        $obj->type 	    = "actor";
        $obj->class 	= "actor";
        if ($actorarea != null){
            $actorarea->addElement($obj);
        }
    } else if ($item->category->name == "data") {
        $obj->type 		= "data";
        $obj->class 	= "component_data";
        if ($dataarea != null){
            $dataarea->addElement($obj);
        }
    } else if ($item->category->name == "device") {
        $obj->class 	= "component_device";
        if ($resourcesarea != null){
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "process") {
    } else if ($item->category->name == "server") {
        $obj->class 	= "server";
        if ($resourcesarea != null){
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "service") {
    } else if ($item->category->name == "software") {
        $obj->class 	= "component_software";
        if ($resourcesarea != null){
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "solution") {
        $obj->type 		= "solution";
        $obj->class 	= "component_software";
        if ($solutionarea != null){
            $solutionarea->addElement($obj);
        }
    } else {
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
    	$servicearea->addElement($obj);
    }
}
// Afficher le résultat
display(array($rootarea));
$dao->disconnect();
require("../svg/footer.php");
?>