<?php
require("../../svg/header.php");
require("../../api/dao.php");
$dao = getDAO("items");
$dao->connect();
$db = $dao->getDB();
require("../dao/db/util.php");
$view = null;
if (isset($_GET["view"])){
	$view = $_GET["view"];
}
$nbelements = 3;
if (isset($_GET["fill"])){
	$fill = $_GET["fill"];
	if ($fill == "no"){
		$nbelements = 0;
	}
}
$areas = $dao->getViewByName($view);
require("../../svg/body.php");
//header('Content-Type: image/svg+xml'); ne fonctionne pas car le type mime n'est pas reconnu
function addElementsToAreas($areas,$nbelements){
	$roots = array();
	foreach($areas as $area){
		$area->needed = true;
		if ($area->parent_id == ""){
			$roots[] = $area;
		}
		if (count($area->subareas) == 0) {
			for ($i = 0; $i < $nbelements; $i++){
				$element            = new stdClass();
				$element->display   = stdClass();
				$element->display->class = "box";
				$element->type      = "box";
				$element->id        = "";
				$element->name      = "";
				$area->elements[]   = $element;
			}
		}
	}
	return $roots;
}
$roots = addElementsToAreas($areas,$nbelements);
display($roots);
$dao->disconnect();
require("../../svg/footer.php");
?>