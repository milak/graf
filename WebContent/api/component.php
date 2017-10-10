<?php
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");
$sql = <<<SQL
    SELECT component.*, data.name as data_name, service.name as service_name, device.name as device_name, software.name as software_name
	FROM component
    INNER JOIN service_needs_component ON service_needs_component.component_id = component.id
	LEFT OUTER JOIN data 		ON component.data_id 		= data.id
	LEFT OUTER JOIN service 	ON component.service_id 	= service.id
	LEFT OUTER JOIN device 		ON component.device_id 		= device.id
	LEFT OUTER JOIN software	ON component.software_id 	= software.id
SQL;
if (isset($_GET["id"])){
	$sql .= " where component.id = ".$_GET["id"];
}
if (isset($_GET["service"])){
	$sql .= " where service_needs_component.service_id = ".$_GET["service"];
}
$sql .= " ORDER by component.id";
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "components" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}
	$type = $row["type"];?>
	{
		"id"    	:  <?php echo $row["id"]; ?>,
		"type" 		: "<?php echo $type;      ?>",
		"area_id"	:  <?php echo $row["area_id"];   ?>,
		"name"		: "<?php 
	if ($type == "device"){
		echo $row["device_name"].'", "device_id" : '.$row["device_id"];
	} else if ($type == "service"){
		echo $row["service_name"].'", "service_id" : '.$row["service_id"];
	} else if ($type == "software"){
		echo $row["software_name"].'", "software_id" : '.$row["software_id"];
	} else if ($type == "data"){
		echo $row["data_name"].'", "data_id" : '.$row["data_id"];
	} else {
		echo "???";
	}?>
	}<?php
	$first = false;
}?>
]}<?php
$result->free();
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST["id"])){ // Update Component
		$id = intval($_POST["id"]);
		if (!isset($_POST["area_id"])){
			die("Missing area_id argument");
		} else {
			$area_id = intval($_POST["area_id"]);
		}
$sql = <<<SQL
    update component set area_id = $area_id where id = $id
SQL;
		if(!$result = $db->query($sql)){
    		die('There was an error running the query [' . $db->error . ']');
		}
	} else {	// Create component
		$attributes = "";
		$values 	= "";
		if (!isset($_POST["service"])){
			die("Missing service argument");
		} else {
			$service = intval($_POST["service"]);
		}
		if (!isset($_POST["area_id"])){
			die("Missing area_id argument");
		} else {
			$area_id = intval($_POST["area_id"]);
		}
		if (!isset($_POST["type"])){
			die("Missing type argument");
		} else {
			$type = $_POST["type"];
		}
		if ($type == "service"){
			if (!isset($_POST["service_id"])){
				die("Missing service_id argument");
			}
			$service_id = intval($_POST["service_id"]);
			$attributes = "service_id";
			$values = $service_id;
		} else if ($type == "device") { 
			if (!isset($_POST["device_id"])){
				die("Missing device_id argument");
			}
			$service_id = intval($_POST["device_id"]);
			$attributes = "device_id";
			$values = $device_id;
		} else if ($type == "data") {
			if (!isset($_POST["data_id"])){
				die("Missing data_id argument");
			}
			$data_id = intval($_POST["data_id"]);
			$attributes = "data_id";
			$values = $data_id;
		} else if ($type == "software") {
			if (!isset($_POST["software_id"])){
				die("Missing software_id argument");
			}
			$software_id = intval($_POST["software_id"]);
			$attributes = "software_id";
			$values = $software_id;
		} else {
			die("Unsupported type value : ".$type);
		}
$sql = <<<SQL
    insert into component (area_id,type,$attributes) values ($area_id,'$type',$values)
SQL;
		if(!$result = $db->query($sql)){
    		die('There was an error running the query [' . $db->error . ']');
		}
		$component_id = $db->insert_id;
		// Insert link to service
$sql = <<<SQL
    insert into service_needs_component (service_id,component_id) values ($service,$component_id)
SQL;
		if(!$result = $db->query($sql)){
   		 	die('There was an error running the query [' . $db->error . ']');
		}
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){
		die("Missing id argument");
	}
	$id = $_GET["id"];
$sql = <<<SQL
    delete from service_needs_component where component_id = $id
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
$sql = <<<SQL
    delete from component_link where from_component_id = $id or to_component_id = $id
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
$sql = <<<SQL
    delete from component where component_id = $id
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
}
