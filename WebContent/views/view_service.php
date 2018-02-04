<?php
require("../svg/header.php");
require("../dao/dao.php");
require("../svg/body.php");
$dao->connect();
$areas = $dao->getViewByName("service");
$rootarea       = $areas["root"];
$processarea    = $areas["process"];
$actorarea      = $areas["actor"];
$resourcesarea  = $areas["resource"];
$dataarea       = $areas["data"];
$solutionarea   = $areas["solution"];
// ****************************************************************
// Chercher tous les noeuds correspondant aux critères de recherche
// ****************************************************************
if (isset($_GET["id"])){
    $itemId = $_GET["id"];
} else {
    displayErrorAndDie('Missing id argument');
}
$service = $dao->getItemById($itemId);
$rootarea->name = $rootarea->name." ".$service->name;
$items = $dao->getRelatedItems($itemId,"*","down");
foreach ($items as $item){
    $obj = new stdClass();
    $obj->id 		= $item->id;
    $obj->type 		= $item->category->name;
    $obj->name 		= $item->name;
    $obj->links 	= array();
    if ($item->category->name == "actor") {
        $obj->class 	= "actor";
        if ($actorarea != null){
            $actorarea->addElement($obj);
        }
    } else if ($item->category->name == "data") {
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
        $obj->class 	= "server";
        if ($processarea != null){
            $processarea->addElement($obj);
        }
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
        $obj->class 	= "component_software";
        if ($solutionarea != null){
            $solutionarea->addElement($obj);
        }
    } else {
        $obj->class 	= "component_software";
        if ($resourcesarea != null){
            $resourcesarea->addElement($obj);
        }
    }
}
// Afficher le résultat
display(array($rootarea));
$dao->disconnect();
require("../svg/footer.php");
?>