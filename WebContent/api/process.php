<?php
header("Content-Type: application/json");
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
$sql = <<<SQL
    SELECT process.*, domain.name as domain_name from process
    INNER JOIN domain ON domain.id = process.domain_id
SQL;
if (isset($_GET["id"])){
	$sql.=" where process.id = ".$_GET["id"];
}
if (isset($_GET["domain_id"])){
	$sql.=" where domain.id = ".$_GET["domain_id"];
}
if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
}?>
{ "process" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"             : <?php echo $row["id"]; ?>,
		"name"           : "<?php echo $row["name"]; ?>",
		"domain_id"      : "<?php echo $row["domain_id"]; ?>",
		"domain_name"    : "<?php echo $row["domain_name"]; ?>"
	}<?php
	$first = false;
}
$result->free();
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["name"])){
		die("Missing name argument");
	}
	$name = $db->real_escape_string($_POST["name"]);
	if (strlen($name) < 4){
		die("Name argument too short");
	}
	if (!isset($_POST["description"])){
		die("Missing description argument");
	}
	$description = $db->real_escape_string($_POST["description"]);
	if (strlen($description) < 5){
		die("Description argument too short");
	}
	if (!isset($_POST["domain_id"])){
		die("Missing domain_id argument");
	}
	$domain_id = intval($_POST["domain_id"]);
	error_log("Création d'un processus : ".$name);
$sql = <<<SQL
	insert into process (name,description,domain_id) values ('$name','$description',$domain_id)
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
	$process_id = $db->insert_id;
$sql = <<<SQL
	insert into process_step (process_id,name,step_type_id) values ($process_id,'start',(select id from step_type where name = "START"))
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_REQUEST["id"])){
		die("Missing id argument");
	}
	$process_id = $_REQUEST["id"];
$sql = <<<SQL
	delete from step_link where process_id = $process_id
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
$sql = <<<SQL
	delete from process_step where process_id = $process_id
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
$sql = <<<SQL
	delete from process where id = $process_id
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
}
require("../db/disconnect.php");
?>
]}
