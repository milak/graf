<?php
require("../svg/header.php");
require("../dao/dao.php");
$dao->connect();
$db = $dao->getDB();
require("../dao/db/util.php");
$areas = loadAreas($db,"strategique");
require("../svg/body.php");

// Analyse des critères de recherche


// ****************************************************************
// Chercher tous les noeuds correspondant aux critères de recherche
// ****************************************************************
$sql = <<<SQL
    SELECT domain.*
    FROM domain
SQL;
$showProcess = false;
if (isset($_GET["showProcess"])){
	$showProcess = true;
}
if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}
$domains = array();
// Charger tous les noeuds dans leur zone respective
while($row = $result->fetch_assoc()){
	$areaid = $row['area_id'];
	$area = $areas[$areaid];
	if ($showProcess){
		$domainAsArea			= new stdClass();
		$domainAsArea->id		= $row["id"];
		$domainAsArea->code		= $row["name"];
		$domainAsArea->name		= $row["name"];
		$domainAsArea->needed	= true;
		$domainAsArea->display	= "vertical";
		$domainAsArea->subareas = array();
		$domainAsArea->elements = array();
		$area->subareas[]		= $domainAsArea;
		$domains[$domainAsArea->id] = $domainAsArea;
	} else {
		$domain 			= new stdClass();
		$domain->id 		= $row["id"];
	    $domain->class		= "rect_180_80";
	    $domain->type		= "domain";
		$domain->name		= $row["name"];
		$area->elements[]	= $domain;
	}
}
$result->free();
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
// Conserver uniquement les zones racines nécessaires et forcer toutes les zones à visibles
$roots = array();
foreach ($areas as $area){
	$area->needed = true;
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