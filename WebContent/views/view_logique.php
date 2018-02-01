<?php
require("../svg/header.php");
require("../dao/dao.php");
require("../svg/body.php");
require("util.php");
if (isset($_GET["id"])){
    $itemId = $_GET["id"];
} else {
    displayErrorAndDie("Missing id argument");
}
$dao->connect();
$areas = $dao->getViewByName("logical");
$rootarea       = $areas["root"];
if (isset($areas["instance"])){
    $instancearea = $areas["instance"];
} else {
    $instancearea = null;
}
if (isset($areas["default"])){
    $defaultarea  = $areas["default"];
} else {
    $defaultarea  = null;
}
// Afficher la description de la solution
$documents = $dao->getItemDocuments($itemId,"TOSCA");
if (count($documents) != 0){
    $document = $documents[0];
    $content = $dao->getDocumentContent($document->id);
} else {
    http_response_code(404);
    die();
    return;
}
error_log($content);
$elements = parseTOSCA($content);
foreach ($elements as $element){
    $element->class 	= "rect_100_100";
    if (isset($element->area)){
        if (isset($areas[$element->area])){
            $area = $areas[$element->area];
        } else {
            $area = $defaultarea;
        }
    } else {
        $area = $defaultarea;
    }
    if ($area != null){
        $area->addElement($element);
    }
}
// Afficher les instances 
$solution = $dao->getItemById($itemId);
$rootarea->name = $rootarea->name." ".$solution->name;
$items = $dao->getRelatedItems($itemId,"*","down");
foreach($items as $item){
    $obj = new stdClass();
    $obj->id 		= $item->id;
    $obj->name 		= $item->name;
    $obj->class 	= "rect_100_100";
    $obj->links 	= array();
    if ($item->category->name=="solution"){
        $obj->type 		= "instance";
        if ($instancearea != null){
            $instancearea->addElement($obj);
        }
    } else {
        continue;
        // TODO que fait-on des autres objets ?
        //$obj->type 		= "item";
        //if ($defaultarea != null){
        //    $defaultarea->addElement($obj);
        //}
    }
}
// ****************************************************************
// Chercher tous les noeuds correspondant aux critères de recherche
// ****************************************************************
$roots = array($rootarea);
// Afficher le résultat
display($roots);
$dao->disconnect();
require("../svg/footer.php");
?>