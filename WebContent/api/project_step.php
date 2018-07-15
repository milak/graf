<?php
require("../api/dao.php");
$dao = getDAO("core");
$dao->connect();
$db = $dao->getDB();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");
$sql = <<<SQL
    SELECT * from project_step
SQL;
if (isset($_GET["id"])){
	$sql .= " where id = ".$_GET["id"];
}
if(!$result = $db->query($sql)){?>
{
   	"code"		: 12,
   	"message"	: "<?php echo 'There was an error running the query [' . $db->error . ']';?>",
   	"objects"	: []
}<?php
} else {?>
{
	"code"		: 0,
	"message"	: "ok",
	"objects"	: [
<?php
	$first = true;
	$firstStep = true;
	$projectId = null;
	$currentPhaseId = null;
	while($row = $result->fetch_assoc()){
		$phaseId = $row["phase_id"];
		if ($first != true) {
			echo ",\n";
		}?>
	{
		"project_id"		: "<?php echo $row["project_id"]; ?>",
		"id" 			: "<?php echo $row["id"]; ?>",
		"phase_id" 		: "<?php echo $phaseId; ?>",
		"code"    		: "<?php echo $row["code"]; ?>",
		"status" 		: "<?php echo $row["status"]; ?>"<?php
		$first = false;?>
	}
<?php }//while ?>
	]
}<?php
	$result->free();
}
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["status"])){
		die("Missing status argument");
	}
	$status = $db->real_escape_string($_POST["status"]);
	if (!isset($_POST["step_id"])){
		$code = $db->real_escape_string($_POST["code"]);
		$phase_id = intval($_POST["phase_id"]);
		$project_id = intval($_POST["project_id"]);
		$sql = <<<SQL
    insert into project_step (code,status,phase_id,project_id) values ('$code','$status',$phase_id,$project_id)
SQL;
		$step_id = $db->insert_id;
	} else {
		$step_id = intval($_POST["step_id"]);
		$sql = <<<SQL
    update project_step 
	set status = '$status'
	where id = $step_id
SQL;
	}
	if(!$result = $db->query($sql)){
		error_log($db->error);?>
		{
			"code"		: 12,
			"message"	: "<?php echo $db->error?>",
			"objects"	: []
		}
		<?php  			return;
	} else {?>
		{
			"code"		: 0,
			"message"	: "ok",
			"objects"	: [{
				"id" : <?php echo $step_id ?>,
				"status" : "<?php echo $status ?>"
			}]
		}<?php
	}
/** METHOD DELETE ** /
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){
		die("Missing id argument");
	}
	$id = intval($_GET["id"]);
	error_log("Removing device ".$id);
$sql = <<<SQL
    delete from data where id = $id
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
}**/
}
$dao->disconnect();
?>