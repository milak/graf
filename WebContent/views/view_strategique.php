<?php
require("../svg/header.php");
require("../dao/dao.php");
$dao->connect();
$areas = $dao->getViewByName("strategic");
require("../svg/body.php");
// Conserver uniquement les zones racines nécessaires et forcer toutes les zones à visibles
$roots = array();
$roots[] = $areas["root"];
// ****************************************************************
// Chercher tous les noeuds correspondant aux critères de recherche
// ****************************************************************
$domains = $dao->getItemsByCategory("domain");
$showProcess = false;
if (isset($_GET["showProcess"])){
	$showProcess = true;
}
$domainsByName = array();
// Charger tous les noeuds dans leur zone respective
foreach($domains as $domain){
    $areaid = $domain->area_id;
    $domainsByName[$domain->name] = $domain;
    // Si le nom du domaine correspond à une zone, on n'ajoute le domaine que s'il est vide
    if (($areaid == $domain->name) && (isset($areas[$areaid]))){
        $area = $areas[$domain->name];
        $domain->items = $dao->getRelatedItems($domain->id,"actor","down");
        if (count($domain->items) > 0){
            foreach($domain->items as $item){
                $item->type = "actor";
                $item->class = "actor";
                $area->addElement($item);
            }
            continue;
        }
    }
	if (!isset($areas[$areaid])){
	    $area = $areas["default"];
	} else {
	    $area = $areas[$areaid];
	}
	if ($showProcess){
		$domainAsArea			= new Area();
		$domainAsArea->id		= $domain->id;
		$domainAsArea->code		= $domain->name;
		$domainAsArea->name		= $domain->name;
		$domainAsArea->setNeeded();
		$domainAsArea->display	= "vertical";
		$area->subareas[]		= $domainAsArea;
		$area->setNeeded();
		$domains[$domainAsArea->id] = $domainAsArea;
	} else {
		$domainAsElement 			= new Area();
		$domainAsElement->id 		= $domain->id;
		$domainAsElement->class		= "rect_180_80";
		$domainAsElement->type		= "domain";
		$domainAsElement->name		= $domain->name;
		$area->addElement($domainAsElement);
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
		$domain->addElement($process);
	}
	$result->free();
}
// Afficher le résultat
display($roots);
$dao->disconnect();
require("../svg/footer.php");
?>