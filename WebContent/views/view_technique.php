<?php
require("../svg/header.php");
require("../db/connect.php");
require("../db/util.php");
$areas = loadAreas($db,"technique");
$tags = loadTags($db);
require("../svg/body.php");
$filter_tag = "";
if (isset($_GET["tags"])){
	$filter_tag = $_GET["tags"];
}
//header('Content-Type: image/svg+xml'); ne fonctionne pas car le type mime n'est pas reconnu

// Analyse des critères de recherche


// ****************************************************************
// Chercher tous les noeuds correspondant aux critères de recherche
// ****************************************************************
$sql = <<<SQL
    SELECT node.id as node_id, node.area_id as area_id, node.name as node_name, node_type.class as node_class, machine.id as machine_id, machine.fqdn, machine.alias, node_has_tag.tag_id as tag_id
    FROM node
    INNER JOIN node_type ON node.node_type_id = node_type.id
    LEFT OUTER JOIN machine ON node.id = machine.node_id
    INNER JOIN area ON node.area_id = area.id
    LEFT OUTER JOIN node_has_tag ON node.id = node_has_tag.node_id
SQL;
// Appliquer les filtres
$filter_tag_as_id_list = "";
if ($filter_tag != ""){
   $filter_tag_as_array = explode(",",$filter_tag);
   foreach($filter_tag_as_array as $value){
	foreach($tags as $tag){
		$found = false;
		if ($tag->value == $value){
			if ($filter_tag_as_id_list != ""){
				$filter_tag_as_id_list .= ",";
			}
			$filter_tag_as_id_list .= $tag->id;
			$found = true;
			break;
		}
	}
	if (!$found) {
		displayErrorAndDie('There was an error applying tags filter [Tag "'.$value.'" not found]');
	}
   }
   $sql .= " WHERE node_has_tag.tag_id in (".$filter_tag_as_id_list.")";
}
$sql.= " ORDER BY node.id,machine.id";
if(!$result = $db->query($sql)){
    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
}
// Charger tous les noeuds dans leur zone respective
$currentnodeid = -1;
$currentmachineid = -1;
$node = null;
while($row = $result->fetch_assoc()){
	$nodeid = $row['node_id'];
	if ($nodeid != $currentnodeid){ // Changement de noeud
		$node = new stdClass();
		$node->id = $nodeid;
                $node->class = $row["node_class"];
		$node->name = $row["node_name"];
		$node->machines = array();
		$node->tags = array();
		$areaid = $row['area_id'];
		// Marquer la zone comme nécessaire
		$area = $areas[$areaid];
		$area->elements[] = $node;
		if (!$area->needed) {
			$area->needed = true; // indiquer qu'elle est nécessaire ainsi que sa zone parente et récursivement
			$parent = $area->parent;
			while ($parent != null){
				$parent->needed=true;
				$parent = $parent->parent;
			}
		}
		$currentnodeid = $nodeid;
	}
	$machineid = $row['machine_id'];
	if ($machineid != "" && $currentmachineid != $machineid){
		$machine = new stdClass();
		$machine->id = $machineid;
		$machine->fqdn 	= $row['fqdn'];
		$machine->alias = $row['alias'];
		$node->machines[] = $machine;
		$currentmachineid = $machineid;
	}
	$tagid = $row['tag_id'];
	if ($tagid != ""){
		$found = false;
		foreach($node->tags as $tag){
			if ($tag->id == $tagid){
				$found = true;
				break;
			}
		}
		if (!$found) {
			$node->tags[] = $tags[$tagid];
		}
	}
}
$result->free();
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
require("../db/disconnect.php");
require("../svg/footer.php");
?>
