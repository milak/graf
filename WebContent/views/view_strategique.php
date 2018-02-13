<?php
require("../svg/header.php");
require("../dao/dao.php");
require("../svg/body.php");
$dao->connect();
$areas = $dao->getViewByName("strategic");
$roots = array();
$roots[] = $areas["root"];
$id = null;
if (isset($_GET["id"])){
	$id = $_GET["id"];
	$item = $dao->getItems((object)['id'=>$id])[0];
	// Si ce n'est pas un domaine, on va rechercher un domaine en remontant
	if ($item->category->name != "domain"){
		$up = $dao->getRelatedItems($id,"up");
		foreach ($up as $overitem){
			error_log($overitem->name);
			if ($overitem->category->name == "domain"){
				$id = $overitem->id;
				break;
			}
		}
		// TODO
	}
}
// **************************
// Chercher tous les domaines
// **************************
$domains = $dao->getItems((object)['category'=>'domain']);
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
                $item->type           = "actor";
                $item->display        = new stdClass();
                $item->display->class = "actor";
                $area->addElement($item);
            }
            continue;
        }
    }
    // Si la zone n'est pas trouvée, prendre la zone par défaut
	if (!isset($areas[$areaid])){
	    $area = $areas["default"];
	} else {
	    $area = $areas[$areaid];
	}
	/*if ($showProcess){
		$domain->code			= $domain->name;
		$domain->setNeeded();
		$domain->display		= "vertical";
		$area->subareas[]		= $domain;
		$area->setNeeded();
		$domains[$domain->id] 	= $domain;
	} else {*/
		$domain->display        = new stdClass();
		$domain->display->class = "rect_180_80";
		$domain->type		    = "domain";
		$area->addElement($domain);
	//}
	if ($id != null){ // si c'est le domaine sélectionné
		if ($domain->id == $id){
			$domain->display->selected = true;
		}
	}
}
// Afficher le résultat
display($roots);
$dao->disconnect();
require("../svg/footer.php");
?>