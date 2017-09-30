<?php
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Create component
	$attributes = "";
	$values 	= "";
	if (!isset($_POST["service"])){
		die("Missing service argument");
	} else {
		$service = intval($_POST["service"]);
	}
	if (isset($_POST["service_id"])){
		$service_id = intval($_POST["service_id"]);
		$attributes = "service_id";
		$values = $service_id;
	} else if (isset($_POST["device_id"])){
		$service_id = intval($_POST["device_id"]);
		$attributes = "device_id";
		$values = $device_id;
	} else if (isset($_POST["data_id"])){
		$data_id = intval($_POST["data_id"]);
		$attributes = "data_id";
		$values = $data_id;
	} else if (isset($_POST["software_id"])){
		$software_id = intval($_POST["software_id"]);
		$attributes = "software_id";
		$values = $software_id;
	}
$sql = <<<SQL
    insert into component ($attributes) values ($values)
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
	$component_id = $db->insert_id
// Insert link to service
$sql = <<<SQL
    insert into service_needs_component (service_id,component_id) values ($service,$component_id)
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
}
