<?php
header("Content-Type: application/json");
require("../db/connect.php");
require("../db/util.php");
function recursiveDisplayArea($level,$area){
	$tab = "";
	for ($i = 0; $i < $level; $i++){
		$tab .= "\t";
	}
	echo $tab."{\"name\"     : \"".$area->name."\",\n";
	echo $tab." \"code\"     : \"".$area->code."\",\n";
	echo $tab." \"display\"  : \"".$area->display."\",\n";
	echo $tab." \"position\" : \"".$area->position."\",\n";
	echo $tab." \"areas\"    : [\n";
	$first = true;
	foreach($area->subareas as $subarea){
		if (!$first){
			echo ",";
		}
		$first = false;
		recursiveDisplayArea($level+1,$subarea);
	}
	echo $tab."]";
	echo $tab."}";
}
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
if (isset($_GET["view"])){
	$viewName = $_GET["view"];
	$areas = loadAreas($db,$viewName);
	if (count($areas) == 0){
		die("View not found");
	}
	$roots = array();
	foreach($areas as $area){
		if ($area->parent == null){
			$roots[] = $area;
		}
	}
	if (count($roots) == 0){
		die("No root found");
	}
	echo "{ \"view\" : {";
	echo "		\"name\"  : \"".$viewName."\",\n";
	echo "		\"areas\" : [\n";
	$first = true;
	foreach($roots as $area){
		if (!$first){
			echo ",";
		}
		$first = false;
		recursiveDisplayArea(1,$area);
	}
	echo "           ]}\n";
	echo "}";
} else {
$sql = <<<SQL
    SELECT * from view
SQL;
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "views" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    : <?php echo $row["id"]; ?>,
		"name" : "<?php echo $row["name"]; ?>"
	}<?php
	$first = false;
}
$result->free();
echo "]}";
}
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$view = $_POST["name"];
	$valueJSON = $_POST["value"];
	$value = json_decode($valueJSON);
	var_dump($value);
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
}
require("../db/disconnect.php");
?>
