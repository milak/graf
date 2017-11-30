<?php
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");

$sql = <<<SQL
	select step_link.*, process_step.name as to_step_name from step_link
	LEFT OUTER JOIN process_step ON step_link.to_step_id = process_step.id
SQL;
if (isset($_GET["process_id"])){
	$process_id = $_GET["process_id"];
	$sql .= " where process_id = $process_id";
}
if (isset($_GET["from_step_id"])){
	$from_step_id = $_GET["from_step_id"];
	$sql .= " where from_step_id = $from_step_id";
}
if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
}?>
{ "links" : [<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"process_id"	: 	<?php echo $row["process_id"]; ?>,
		"label"			: 	"<?php echo $row["label"]; ?>",
		"data"			: 	"<?php echo $row["data"]; ?>",
		"from_step"	: 	{ "id" : <?php echo $row["from_step_id"]; ?>, "name" : "TODO"},
		"to_step"	: 	{ "id" : <?php echo $row["to_step_id"]; ?>, "name" : "<?php echo $row["to_step_name"]; ?>" }
	}
	<?php
	$first = false;
}
$result->free();
?>
]}<?php
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["process_id"])){
		die("Missing process_id argument");
	}
	$process_id = intval($_POST["process_id"]);
	if (!isset($_POST["label"])){
		die("Missing label argument");
	}
	$label = $db->real_escape_string($_POST["label"]);
	if (!isset($_POST["data"])){
		die("Missing data argument");
	}
	$data = $db->real_escape_string($_POST["data"]);
	if (!isset($_POST["from_step_id"])){
		die("Missing from_step_id argument");
	}
	$from_step_id = intval($_POST["from_step_id"]);
	if (!isset($_POST["to_step_id"])){
		die("Missing to_step_id argument");
	}
	$to_step_id = intval($_POST["to_step_id"]);
$sql = <<<SQL
	insert into step_link (process_id,from_step_id,to_step_id,label,data) values ($process_id,$from_step_id,$to_step_id,'$label','$data')
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']'.$sql);
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_REQUEST["process_id"])){
		die("Missing process_id argument");
	}
	$process_id = $_REQUEST["process_id"];
	if (!isset($_REQUEST["from_step_id"])){
		die("Missing from_step_id argument");
	}
	$from_step_id = intval($_REQUEST["from_step_id"]);
	if (!isset($_REQUEST["to_step_id"])){
		die("Missing to_step_id argument");
	}
	$to_step_id = intval($_REQUEST["to_step_id"]);
$sql = <<<SQL
	delete from step_link 
	where 	process_id 		= $process_id 
	and		from_step_id	= $from_step_id
	and		to_step_id		= $to_step_id
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']'.$sql);
	}
}
require("../db/disconnect.php");
?>
