<?php
header("Content-Type: application/json");
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
$sql = <<<SQL
    SELECT service.*, instance.id as instance_id, instance.name as instance_name, environment.id as environment_id, environment.name as environment_name, environment.code as environment_code from service
    LEFT OUTER JOIN instance ON service.id = instance.service_id
    LEFT OUTER JOIN environment ON instance.environment_id = environment.id
SQL;
if (isset($_GET["id"])){
	$sql .= " where service.id = ".$_GET["id"];
}
$sql .= " ORDER by service.name, service.id";
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "services" : [
<?php
$first = true;
$first_instance = true;
$current_service_id = -1;
while($row = $result->fetch_assoc()){
	$service_id = $row["id"];
	if ($current_service_id != $service_id){
		$current_service_id = $service_id;
		$first_instance = true;
		if ($first != true) {?>
					]
		},
<?php		}?>
		{
			"id"        : <?php echo $service_id; ?>,
			"name"      : "<?php echo $row["name"]; ?>",
			"code"      : "<?php echo $row["code"]; ?>",
			"instances" : [
<?php	}
	if (isset($row["instance_id"])){
		if ($first_instance != true) {
			?>,
<?php
		}
		$first_instance = false;
?>							{
								"id"   : "<?php echo $row["instance_id"]; ?>",
								"name" : "<?php echo $row["instance_name"]; ?>",
								"environment" : { "id" : "<?php echo $row["environment_id"]; ?>", "code" : "<?php echo $row["environment_code"]; ?>", "name" : "<?php echo $row["environment_name"]; ?>"}
							}<?php
	}
	$first = false;
}
$result->free();
require("../db/disconnect.php");
if ($first != true) {?>
					]
		}
<?php } ?>
]}
<?php
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["domain_id"])){
		die("Missing domain_id argument");
	}
	$domain_id = intval($_POST["domain_id"]);
	if (!isset($_POST["code"])){
		die("Missing code argument");
	}
	$code = $db->real_escape_string($_POST["code"]);
	if (!isset($_POST["name"])){
		die("Missing code argument");
	}
	$name = $db->real_escape_string($_POST["name"]);
error_log("Création d'un service : ".$name." domaine ".$domain_id);
$sql = <<<SQL
	insert into service (code,name,domain_id) values ('$code','$name',$domain_id)
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
	delete from service where id = $id
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
}
