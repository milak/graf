<?php
header("Content-Type: application/json");
require("../db/connect.php");
$sql = <<<SQL
    SELECT service.*, instance.id as instance_id, instance.name as instance_name, environment.id as environment_id, environment.name as environment_name, environment.code as environment_code from service
    LEFT OUTER JOIN instance ON service.id = instance.service_id
    LEFT OUTER JOIN environment ON instance.environment_id = environment.id
    ORDER by service.id
SQL;
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
