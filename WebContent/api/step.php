<?php
header("Content-Type: application/json");
require("../db/connect.php");
$sql = <<<SQL
    SELECT process_step.*, step_type.name as step_type_name from process_step
    INNER JOIN step_type ON step_type.id = process_step.step_type_id
SQL;
if (isset($_GET["id"])){
	$sql.=" where process_step.id = ".$_GET["id"];
}
if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
}?>
{ "steps" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"             : <?php echo $row["id"]; ?>,
		"name"           : "<?php echo $row["name"]; ?>",
		"process_id"     : "<?php echo $row["process_id"]; ?>",
		"step_type_id"   : "<?php echo $row["step_type_id"]; ?>",
		"step_type_name" : "<?php echo $row["step_type_name"]; ?>",
		"service_id"     : "<?php echo $row["service_id"]; ?>",
		"actor_id"       : "<?php echo $row["actor_id"]; ?>",
		"sub_process_id" : "<?php echo $row["sub_process_id"]; ?>"
	}<?php
	$first = false;
}
$result->free();
require("../db/disconnect.php");
?>
]}
