<?php
require("../svg/header.php");
require("../dao/dao.php");
$dao->connect();
$areas = $dao->getViewByName("strategique");
require("../svg/body.php");
// Conserver uniquement les zones racines nécessaires et forcer toutes les zones à visibles
$roots = array();
foreach ($areas as $area){
    $area->needed = true;
    if ($area->parent != null){
        continue;
    }
    $roots[] = $area;
}
$areas["default"]->needed=false;
// ****************************************************************
// Chercher tous les noeuds correspondant aux critères de recherche
// ****************************************************************
$domains = $dao->getDomains();
$showProcess = false;
if (isset($_GET["showProcess"])){
	$showProcess = true;
}
// Charger tous les noeuds dans leur zone respective
foreach($domains as $domain){
    $areaid = $domain->area_id;
	$area = $areas[$areaid];
	if ($area == null) {
	    $area = $areas["default"];
	}
	if ($showProcess){
		$domainAsArea			= new stdClass();
		$domainAsArea->id		= $domain->id;
		$domainAsArea->code		= $domain->name;
		$domainAsArea->name		= $domain->name;
		$domainAsArea->needed	= true;
		$domainAsArea->display	= "vertical";
		$domainAsArea->subareas = array();
		$domainAsArea->elements = array();
		$area->subareas[]		= $domainAsArea;
		$area->needed = true;
		$domains[$domainAsArea->id] = $domainAsArea;
	} else {
		$domainAsElement 			= new stdClass();
		$domainAsElement->id 		= $domain->id;
		$domainAsElement->class		= "rect_180_80";
		$domainAsElement->type		= "domain";
		$domainAsElement->name		= $domain->name;
		$area->needed = true;
		$area->elements[]	        = $domainAsElement;
	}
}
if ($showProcess){
$sql = <<<SQL
    SELECT *
    FROM process
SQL;
	if(!$result = $db->query($sql)){
 	   displayErrorAndDie('There was an error running the query [' . $db->error . ']');
	}
	while($row = $result->fetch_assoc()){
		$process 			= new stdClass();
		$process->id		= $row["id"];
		$process->class		= "rect_180_80";
		$process->type		= "process";
		$process->name		= $row["name"];
		$domain_id			= $row["domain_id"];
		$domain				= $domains[$domain_id];
		if ($domain == null){
			continue;
		}
		$domain->elements[] = $process;
	}
	$result->free();
}

// Afficher le résultat
display($roots);
$dao->disconnect();
require("../svg/footer.php");
?>