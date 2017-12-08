<?php
require("../dao/dao.php");
$dao->connect();
$db = $dao->getDB();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");
$sql = <<<SQL
    SELECT instance.*, environment.id as environment_id, environment.name as environment_name, environment.code as environment_code from instance
    LEFT OUTER JOIN environment ON instance.environment_id = environment.id
SQL;
if (isset($_GET["id"])){
	$sql .= " where instance.id = ".$_GET["id"];
}
$sql .= " ORDER by instance.id";
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "instances" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if (!$first) {
		?>,<?php
	}?>
	{
		"id"        : <?php echo $row["id"]; ?>,
		"name"      : "<?php echo $row["name"]; ?>",
		"environment" : { "id" : "<?php echo $row["environment_id"]; ?>", "code" : "<?php echo $row["environment_code"]; ?>", "name" : "<?php echo $row["environment_name"]; ?>"}
	}<?php
	$first = false;
}
$result->free();
require("../db/disconnect.php");?>
]}
<?php
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["service_id"])){
		die("Missing service_id argument");
	}
	$service_id = intval($_POST["service_id"]);
	if (!isset($_POST["name"])){
		die("Missing name argument");
	}
	$name = $db->real_escape_string($_POST["name"]);
	if (!isset($_POST["environment_id"])){
		die("Missing environment_id argument");
	}
	$environment_id = intval($_POST["environment_id"]);
$sql = <<<SQL
	insert into instance (name,environment_id,service_id) values ('$name',$environment_id,$service_id)
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
$sql = <<<SQL
	delete from instance where id = $id
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
}
$dao->disconnect();
?>