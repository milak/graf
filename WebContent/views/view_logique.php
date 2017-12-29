<?php
require("../svg/header.php");
require("../dao/dao.php");
$dao->connect();
$areas = $dao->getViewByName("logique");
$rootarea = $areas["root"];
$instancearea = $areas["instance"];
require("../svg/body.php");
if (isset($_GET["id"])){
    $itemId = $_GET["id"];
} else {
    displayErrorAndDie("Missing id argument");
}
$solution = $dao->getItemById($itemId);
$rootarea->name = $rootarea->name." ".$solution->name;
$items = $dao->getSolutionItems($itemId);
foreach($items as $item){
    $obj = new stdClass();
    $obj->id 		= $item->id;
    $obj->type 		= "instance";
    $obj->class 	= "rect_100_100";
    $obj->name 		= $item->name;
    $obj->links 	= array();
    $instancearea->addElement($obj);
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