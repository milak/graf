<?php
require("../../svg/header.php");
require("../../api/dao.php");
$dao = getDAO("items");
require("../../svg/body.php");
require("util.php");
if (isset($_GET["itemId"])){
    $itemId = $_GET["itemId"];
} else {
    displayErrorAndDie("Missing itemId argument");
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
$documents = $dao->getDocuments((object)['itemId' => $itemId, 'type' => 'TOSCA']);
if (count($documents) != 0){
    $document = $documents[0];
    $content = $dao->getDocumentContent($document->id);
} else {
    http_response_code(404);
    die();
    return;
}
$result = parseTOSCA($content);
$elements = $result[0];
$allElementsById = $result[1];
foreach ($elements as $element){
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
// Afficher les éléments rattachés à l'item 
$solution = $dao->getItems((object)['id'=>$itemId])[0];
$rootarea->name = $rootarea->name." ".$solution->name;
$items = $dao->getRelatedItems($itemId,"*","down");
$relatedItemsById = array();
foreach($items as $item){
    $relatedItemsById[$item->id] = $item;
    $obj = new stdClass();
    $obj->id 		       = $item->id;
    $obj->name 		       = $item->name;
    $obj->display          = new stdClass();
    $obj->display->class   = "rect_100_100";
    $obj->links 	       = array();
    // C'est une instance de la solution, on la passer
    if ($item->category->name=="solution"){
        if (strpos($item->name,$solution->name) == 0){
            continue;
        }
    }
    // On l'a déjà
    if (isset($allElementsById[$obj->id])){
        // TODO Il faut fusionner avec ce que l'on a dans le schéma
    } else {
        $obj->type = "tosca_item";
        if ($defaultarea != null){
            $obj->display->dashed = true;
            $defaultarea->addElement($obj);
        }
    }
}
foreach ($allElementsById as $element){
    $element->display           = new stdClass();
    $element->display->class 	= "rect_100_100";
    if (!isset($element->itemId)){
        $element->display->blured = true;
    } else if (!isset($relatedItemsById[$element->itemId])){
        $element->display->dashed = true;
    }
}
// ****************************************************************
// Chercher tous les noeuds correspondant aux critères de recherche
// ****************************************************************
$roots = array($rootarea);
// Afficher le résultat
display($roots);
$dao->disconnect();
require("../../svg/footer.php");
?>