<?php
// ********************
// Chargement des zones
// ********************
function loadTags($db){
$sql = <<<SQL
    SELECT * from tag
SQL;
	if(!$result = $db->query($sql)){
	    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
	}
	$tags = array();
	while($row = $result->fetch_assoc()){
		$tag = new stdClass();
		$tag->id = $row['id'];
		$tag->value = $row['value'];
		$tags[$tag->id] = $tag;
	}
	$result->free();
	return $tags;
}
// ********************
// Chargement des zones
// ********************
function loadAreas($db,$view_name){
$sql = <<<SQL
    SELECT area.* from area
SQL;
	if ($view_name != null){
		$sql .= " INNER JOIN view ON view.id = area.view_id where view.name = '".$view_name."'";
	}
	$sql .= " ORDER by position";
	if (!$result = $db->query($sql)){
	    displayErrorAndDie('There was an error running the query [' . $db->error . ']');
	}
	$areas = array();
	while($row = $result->fetch_assoc()){
		$area = new stdClass();
		$area->id        = $row['id'];
		$area->name      = $row['name'];
		$area->code      = $row['code'];
		$area->parent_id = $row['parent_id'];
		$area->display   = $row['display'];
		$area->position  = $row['position'];
		$area->elements  = array();
		$area->subareas  = array();
		$area->needed    = false;
		$area->parent    = null;
		$areas[$area->id] = $area;
	}
	$result->free();
	// Raccrocher les zones à leur parent
	foreach ($areas as $area){
		if ($area->parent_id != null){
			$areaparent = $areas[$area->parent_id];
			$areaparent->subareas[] = $area;
			$area->parent = $areaparent;
		}
	}
	return $areas;
}
?>
