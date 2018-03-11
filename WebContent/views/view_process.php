<?php
require("../dao/dao.php");
require("util.php");
$dao->connect();
if (isset($_GET["itemId"])){
    $itemId = $_GET["itemId"];
    $process = $dao->getItems((object)['id'=>$itemId])[0];
    $title = "Processus ".$process->name;
} else {
	$title = "Processus";
}
if (isset($_GET["documentId"])){
	$documentId = $_GET["documentId"];
} else {
    die("Missing documentId argument");
}
$documents = $dao->getDocuments((object)['id' => $documentId]);
if (count($documents) != 0){
    $document = $documents[0];
    if ($document->type != 'BPMN'){
    	return;
    }
    $content = $dao->getDocumentContent($documentId);
} else {
    //http_response_code(404);
    //die();
    return;
}
$steps = parseBPMN($content);
require("../svg/header.php");
require("../svg/body.php");

//header('Content-Type: image/svg+xml'); //ne fonctionne pas car le type mime n'est pas reconnu

$areas = array();
$area = new stdClass();
$area->id        = 0;
// TODO retrouver le nom du process
$area->name      = $title;
$area->code      = "";
$area->parent_id = null;
$area->display   = "horizontal";
$area->elements  = array();
$area->subareas  = array();
$area->needed    = true;
$area->parent    = null;
$areas[] = $area;

// Charger toutes les étapes
foreach($steps as $step){
	$step->type 		    = "BPMN";
	$step->display          = new stdClass();
	$step->display->class   = "process_".strtolower($step->type_name);
	$step->allreadyComputed = false;// flag qui sera positionné lorsqu'on aura calculé sa position, cela évite de boucler
	$area->elements[] = $step;
}

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
display($roots);
$dao->disconnect();
require("../svg/footer.php");
?>