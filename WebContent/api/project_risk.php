<?php
require("../api/dao.php");
$dao = getDAO("core");
$dao->connect();
$db = $dao->getDB();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	header("Content-Type: application/json");
	$sql = <<<SQL
	    SELECT * from project_risk
SQL;
	if (isset($_GET["project_id"])){
		$sql .= " WHERE project_id = ".$_GET["project_id"];
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
		$projectId = null;
		while($row = $result->fetch_assoc()){
			if ($first != true) {
				echo ",\n";
			}?>
			{
				"id" 			: "<?php echo $row["id"]; ?>",
				"project_id" 	: <?php echo $row["project_id"]; ?>,
				"description"	: "<?php echo $row["description"]; ?>",
				"probability"	: <?php echo $row["probability"]; ?>,
				"criticity"		: <?php echo $row["criticity"]; ?>
			}<?php
				$first = false;
			}?>	
		]
	}<?php
	}
	$result->free();
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["description"])){?>
		{
			"code"		: 12,
			"message"	: "Missing description argument",
			"objects"	: []
		}<?php
		return;
	}
	$description 	= $db->real_escape_string($_POST["description"]);
	if (!isset($_POST["criticity"])){?>
		{
			"code"		: 12,
			"message"	: "Missing criticity argument",
			"objects"	: []
		}<?php
		return;
	}
	$criticity 		= intval($_POST["criticity"]);
	$probablity 	= intval($_POST["probablity"]);
	if (!isset($_POST["id"])){
		$project_id 	= intval($_POST["project_id"]);
		$sql = <<<SQL
    		insert into project_risk (description,project_id,criticity,probability) values ('$description',$project_id,$criticity,$probablity)
SQL;
	} else {
		$id = intval($_POST["id"]);
		$sql = <<<SQL
			update 	project_risk
			set 	description = '$description', criticity = '$criticity', probablity = '$probablity'
			where 	id = $id
SQL;
	}
	if(!$result = $db->query($sql)){?>
		{
			"code"		: 12,
			"message"	: "There was an error running the query : <?php echo $db->error ?>",
			"objects"	: []
		}<?
	} else {
		if (!isset($_POST["id"])){
			$id = $db->insert_id;
		}?>
		{
			"code"		: 0,
			"message"	: "Ok",
			"objects"	: [{
				"id"	: <?php echo $id ?>
			}]
		}
		<?php
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){?>
		{
			"code"		: 12,
			"message"	: "Missing id argument",
			"objects"	: []
		}<?php
	} else {
		$id = intval($_GET["id"]);
		$sql = <<<SQL
    delete from project_risk where id = $id
SQL;
		if(!$result = $db->query($sql)){?>
			{
				"code"		: 12,
				"message"	: "There was an error running the query : <?php echo $db->error ?>",
				"objects"	: []
			}<?php
		} else {?>
			{
				"code"		: 0,
				"message"	: "Ok",
				"objects"	: []
			}<?php
		}
	}
}
$dao->disconnect();
?>