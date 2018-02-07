<?php
header("Content-Type: application/json");
require("../dao/dao.php");
$dao->connect();
function recursiveDisplayArea($level,$area){
	$tab = "";
	for ($i = 0; $i < $level; $i++){
		$tab .= "\t";
	}
	echo $tab."{\"name\"     : \"".$area->name."\",\n";
	echo $tab." \"id\"       : \"".$area->id."\",\n";
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
	$areas = $dao->getViewByName($viewName);
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
    $views = $dao->getViews();
    $first = true;?>
{ "views" : [
<?php
    foreach ($views as $view){
	   if ($first != true) {
	       echo ",\n";
	   }?>
		{
			"id"    : <?php echo $view->id; ?>,
			"name" : "<?php echo $view->name; ?>"
		}<?php
	   $first = false;
    }
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
$dao->disconnect();
?>