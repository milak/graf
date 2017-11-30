<?php
header("Content-Type: application/json");
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
$sql = <<<SQL
    SELECT process_step.*, step_type.name as step_type_name from process_step
    INNER JOIN step_type ON step_type.id = process_step.step_type_id
SQL;
if (isset($_GET["id"])){
	$sql.=" where process_step.id = ".$_GET["id"];
}
if (isset($_GET["process_id"])){
	$sql.=" where process_step.process_id = ".$_GET["process_id"];
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
		"element_id"     : "<?php echo $row["element_id"]; ?>",
		"sub_process_id" : "<?php echo $row["sub_process_id"]; ?>"
	}<?php
	$first = false;
}
$result->free();
?>
]}
<?php
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["name"])){
		die("Missing name argument");
	}
	$name = $db->real_escape_string($_POST["name"]);
	if (strlen($name) < 3){
		die("Name argument too short");
	}
	if (!isset($_POST["process_id"])){
		die("Missing process_id argument");
	}
	$process_id = intval($_POST["process_id"]);
	if (!isset($_POST["type"])){
		die("Missing type argument");
	}
	$type = $db->real_escape_string($_POST["type"]);
	// Recherche de step_type_id
$sql = <<<SQL
select id from step_type where name = '$type'
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
	if ($result->num_rows != 1){
			die("Type $type not found or too much result ".$result->num_rows. "(".$sql.")");
	}
	$row = $result->fetch_assoc();
	$step_type_id = $row["id"];
	$result->free();
	$id = "";
	$val = "";
	if ($type == "SERVICE"){
		$id = ", service_id";
		if (!isset($_POST["service_id"])){
			die("Missing service_id argument");
		}
		$val = ",".intval($_POST["service_id"]);
	} else if ($type == "ACTOR"){
		$id = ", element_id";
		if (!isset($_POST["element_id"])){
			die("Missing element_id argument");
		}
		$val = ",".intval($_POST["element_id"]);
	} else if ($type == "SUB-PROCESS"){
		$id = ", sub_process_id";
		if (!isset($_POST["sub_process_id"])){
			die("Missing sub_process_id argument");
		}
		$val = ",".intval($_POST["sub_process_id"]);
	}
	error_log("Création d'une étape de processus : ".$name);
$sql = <<<SQL
	insert into process_step (name,step_type_id,process_id $id) values ('$name',$step_type_id,$process_id $val)
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']'.$sql);
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_REQUEST["id"])){
		die("Missing id argument");
	}
	$id = $_REQUEST["id"];
$sql = <<<SQL
	delete from process_step where id = $id
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']'.$sql);
	}
}
require("../db/disconnect.php");
?>
