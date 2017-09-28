<?php
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
require("../db/disconnect.php");
?>
