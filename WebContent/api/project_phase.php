<?php
require("../dao/dao.php");
$dao->connect();
$db = $dao->getDB();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");
$sql = <<<SQL
    SELECT project_phase.id as phase_id, project_phase.code as code, project_phase.project_id as project_id, project_step.id as step_id, project_step.code as step_code, project_step.status as step_status from project_phase
	LEFT JOIN project_step ON project_phase.id = project_step.phase_id
SQL;
if (isset($_GET["project_id"])){
	$sql .= " where project_phase.project_id = ".$_GET["project_id"];
}
$sql .= " ORDER BY project_phase.project_id, project_phase.id";
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
		if ($currentPhaseId != $phaseId) {
			if ($first != true) {
				echo "]\n\t},\n";
			}
			$firstStep = true;
		?>
	{
		"id" 				: "<?php echo $phaseId; ?>",
		"project_id"		: "<?php echo $row["project_id"]; ?>",
		"code"    			: "<?php echo $row["code"]; ?>",
		"steps"				: [<?php 
		}
		$step_code = $row["step_code"];
		if ($firstStep != true){
			echo ",\n";
		}
		$firstStep = false;
		if ($step_code != ""){
			echo "\n";?>
				{
					"id"    		: "<?php echo $row["step_id"]; ?>",
					"code"    		: "<?php echo $row["step_code"]; ?>",
					"status" 		: "<?php echo $row["step_status"]; ?>"
				}<?php
		}
		$currentPhaseId = $phaseId;
		$first = false;
	}?>
<?php
if ($first != true) {
	echo "]\n\t}\n";
}?>
]
}<?php
}
$result->free();
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["code"])){
		die("Missing code argument");
	}
	$code = $db->real_escape_string($_POST["code"]);
	if (!isset($_POST["project_id"])){
		die("Missing project_id argument");
	}
	$project_id = intval($_POST["project_id"]);
$sql = <<<SQL
    insert into project_phase (code,project_id) values ('$code',$project_id)
SQL;
	if(!$result = $db->query($sql)){
		error_log($db->error);?>
		{
			"code"		: 12,
			"message"	: "<?php echo $db->error?>",
			"objects"	: []
		}
		<?php  			return;
	} else {
		$phase_id = $db->insert_id;?>
		{
			"code"		: 0,
			"message"	: "ok",
			"objects"	: [{
				"id" : <?php echo $phase_id ?>
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
	}**/
}
$dao->disconnect();
?>