<?php
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");
$sql = <<<SQL
    SELECT * from component_link
SQL;
if (isset($_GET["from_component_id"])){
	$sql .= " where from_component_id = ".$_GET["from_component_id"];
}
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "links" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"from_component_id"	: <?php echo $row["from_component_id"]; ?>,
		"to_component_id"	: <?php echo $row["to_component_id"]; ?>,
		"protocole"			: "<?php echo $row["protocole"]; ?>",
		"port"				: "<?php echo $row["port"]; ?>"
	}<?php
	$first = false;
}?>
]}<?php
$result->free();
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["from_component_id"])){
		die("Missing from_component_id argument");
	}
	$from_component_id = intval($_POST["from_component_id"]);
	if (!isset($_POST["to_component_id"])){
		die("Missing to_component_id argument");
	}
	$to_component_id = intval($_POST["to_component_id"]);
	if (!isset($_POST["protocole"])){
		die("Missing protocole argument");
	}
	$protocole = $db->real_escape_string($_POST["protocole"]);
	if (!isset($_POST["port"])){
		die("Missing port argument");
	}
	$port = $db->real_escape_string($_POST["port"]);
$sql = <<<SQL
    insert into component_link (from_component_id,to_component_id,protocole,port) values ($from_component_id,$to_component_id,"$protocole","$port")
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["from_component_id"])){
		die("Missing from_component_id argument");
	}
	$from_component_id = intval($_GET["from_component_id"]);
	if (!isset($_GET["to_component_id"])){
		die("Missing to_component_id argument");
	}
	$to_component_id = intval($_GET["to_component_id"]);
	error_log("Removing component_link ".$id);
$sql = <<<SQL
    delete from component_link where from_component_id = $from_component_id and to_component_id = $to_component_id
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
}
require("../db/disconnect.php");
?>
