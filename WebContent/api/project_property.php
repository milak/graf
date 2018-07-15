<?php
require("../api/dao.php");
$dao = getDAO("core");
$dao->connect();
$db = $dao->getDB();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");
$sql = <<<SQL
    SELECT project_property.property_value as value, property.name as name, project_property.project_id from project_property, property
	WHERE project_property.property_id = property.id
SQL;
if (isset($_GET["project_id"])){
	$sql .= " and project_property.project_id = ".$_GET["project_id"];
}
if (isset($_GET["name"])){
	$sql .= " and property.name = '".$_GET["name"]."'";
}
$sql .= ' ORDER BY property.name';
if(!$result = $db->query($sql)){?>
{
   	"code"		: 12,
   	"message"	: "<?php echo 'There was an error running the query [' . $db->error . ']';?>",
   	"objects"	: []
}<?php
} else {?>
{
	"code"		: 0,
	"message"	: "ok",
	"objects"	: [
<?php
	$first = true;
	$projectId = null;
	while($row = $result->fetch_assoc()){
		if ($first != true) {
			echo ",\n";
		}?>
	{
		"project_id" 	: "<?php echo $row["project_id"]; ?>",
		"name"    		: "<?php echo $row["name"]; ?>",
		"value" 		: "<?php echo $row["value"]; ?>"
	}<?php
		$first = false;
	}?>	
	]
}<?php
}
$result->free();
}
/** METHOD POST ** /
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["name"])){
		die("Missing name argument");
	}
	$name = $db->real_escape_string($_POST["name"]);
$sql = <<<SQL
    insert into data (name) values ('$name')
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
/ ** METHOD DELETE ** /
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){
		die("Missing id argument");
	}
	$id = intval($_GET["id"]);
	error_log("Removing device ".$id);
$sql = <<<SQL
    delete from data where id = $id
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
}**/
$dao->disconnect();
?>