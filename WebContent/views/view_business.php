<?php
// header('Content-Type: image/svg+xml'); //ne fonctionne pas car le type mime n'est pas reconnu
require ("../svg/header.php");
require ("../dao/dao.php");
require ("util.php");
require ("../svg/body.php");
$dao->connect();
$areas = $dao->getViewByName("business");
$label = "";
if (isset($_GET["id"])) {
    $id = $_GET["id"];
} else {
    displayErrorAndDie('Need "id" argument');
}
$items = $dao->getItems((object)['id'=>$id]);
foreach ($items as $item) {
	$currentItem = $item;
	break;
}
$direction = 'none';
if (isTechnical($currentItem->category->name)){
	$direction = 'up';
} else if (isBusiness($currentItem->category->name)){
	$direction = 'down';
}
if ($currentItem->category->name == 'data'){
	$direction = 'up';
}
$currentItems = $items;
$domains = array();
$interestingItems = array();
while (count($currentItems) > 0){
	$newItems = array();
	foreach ($currentItems as $item){
		if ($item->category->name == 'domain'){
			$domains[$item->name] = $item;
		} else {
			if ($direction == 'up'){
				if (isBusiness($item->category->name)){
					if ($item->id != $currentItem->id){
						$interestingItems[] = $item;
					}
				}
			}
			foreach($dao->getRelatedItems($item->id,"*","up") as $overitem) {
				$newItems[] = $overitem;
			}
		}
	}
	$currentItems = $newItems;
}
if ($direction == 'down'){
	$currentItems 	= $dao->getRelatedItems($id,"*","down");
	foreach ($currentItems as $subitem) {
		if (isBusiness($subitem->category->name)){
			$interestingItems[] = $subitem;
		}
	}
}




// Chargement des sub items
$actors 	= array();
$process 	= array();
$services	= array();
$rootarea       = $areas["root"];
$rootarea->name = $rootarea->name . " " . $item->name;
$processarea    = $areas["process"];
$domainarea     = $areas["domain"];
$servicearea    = $areas["service"];
$actorarea      = $areas["actor"];
$resourcesarea  = $areas["resource"];
$dataarea       = $areas["data"];
$solutionarea   = $areas["solution"];

foreach ($domains as $domain){
	$domain->type = $domain->category->name;
	$domain->display = new stdClass();
	$domain->display->class = "rect_100_100";
	$domainarea->addElement($domain);
}

foreach ($interestingItems as $item) {
    $obj = new stdClass();
    $obj->id = $item->id;
    $obj->type = $item->category->name;
    $obj->name = $item->name;
    $obj->links = array();
    $obj->display = new stdClass();
    if ($item->category->name == "actor") {
        $obj->display->class = "actor";
        if ($actorarea != null) {
            $actorarea->addElement($obj);
        }
    } else if ($item->category->name == "data") {
        $obj->display->class = "component_data";
        if ($dataarea != null) {
            $dataarea->addElement($obj);
        }
    } else if ($item->category->name == "device") {
        $obj->display->class = "component_device";
        if ($resourcesarea != null) {
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "data") {
        $obj->display->class = "component_device";
        if ($resourcesarea != null) {
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "process") {
        if ($processarea != null) {
            $obj                    = new stdClass();
            $obj->id                = $item->id;
            $obj->type              = $item->category->name;
            $obj->name              = $item->name;
            $obj->links             = array();
            $obj->display           = new stdClass();
            $obj->display->class    = "process_" . strtolower("sub-process");
            $processarea->addElement($obj);
        }
    } else if ($item->category->name == "server") {
        $obj->display->class = "server";
        if ($resourcesarea != null) {
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "service") {
        if ($servicearea != null) {
            $obj = new stdClass();
            $obj->id = $item->id;
            $obj->type = "service";
            $obj->display  = new stdClass();
            $obj->display->class = "process_service";
            $obj->name = $item->name;
            $obj->links = array();
            $servicearea->addElement($obj);
        }
    } else if ($item->category->name == "software") {
        $obj->display->class = "component_software";
        if ($resourcesarea != null) {
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "solution") {
        $obj->display->class = "component_software";
        if ($solutionarea != null) {
            $solutionarea->addElement($obj);
        }
    } else if ($item->category->name == "domain") {
        $obj->display->class = "component_software";
        if ($domainarea != null) {
            $domainarea->addElement($obj);
        }
    } else {
        error_log("Category non prevue '".$item->category->name."'");
    }
}
// Afficher le résultat
display(array($rootarea));
$dao->disconnect();
require ("../svg/footer.php");
?>