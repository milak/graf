<?php
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");
$sql = <<<SQL
    SELECT * from software
SQL;
if (isset($_GET["id"])){
	$sql .= " where id = ".$_GET["id"];
}
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "softwares" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    	: <?php echo $row["id"]; ?>,
		"name" 		: "<?php echo $row["name"]; ?>"
	}<?php
	$first = false;
}?>
]}<?php
$result->free();
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["name"])){
		die("Missing name argument");
	}
	$name = $db->real_escape_string($_POST["name"]);
$sql = <<<SQL
    insert into software (name) values ('$name')
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){
		die("Missing id argument");
	}
	$id = intval($_GET["id"]);
	error_log("Removing software ".$id);
$sql = <<<SQL
    delete from software where id = $id
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
}
require("../db/disconnect.php");
?>
