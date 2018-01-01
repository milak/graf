<?php
require("../svg/header.php");
require("../dao/dao.php");
$dao->connect();
$areas = $dao->getViewByName("logical");
$rootarea = $areas["root"];
$instancearea = $areas["instance"];
$defaultarea = $areas["default"];
require("../svg/body.php");
if (isset($_GET["id"])){
    $itemId = $_GET["id"];
} else {
    displayErrorAndDie("Missing id argument");
}
// Afficher la description de la solution
$structure = $dao->getSolutionStructure($itemId);
foreach ($structure as $element){
    $obj = new stdClass();
    $obj->id 		= $element->id;
    $obj->name 		= $element->name;
    $obj->class 	= "rect_100_100";
    $obj->links 	= array();
    $obj->type 		= "instance";
    if ($defaultarea != null){
        $defaultarea->addElement($obj);
    }
}
// Afficher les instances 
$solution = $dao->getItemById($itemId);
$rootarea->name = $rootarea->name." ".$solution->name;
$items = $dao->getSolutionItems($itemId);
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