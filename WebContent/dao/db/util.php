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
?>