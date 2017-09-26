<?php
header("Content-Type: application/json");
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
$sql = <<<SQL
    SELECT domain.* from domain
SQL;
if (isset($_GET["id"])){
	$sql.=" where domain.id = ".$_GET["id"];
}
$sql.=" order by name";
if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
}?>
{ "domains" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"             : <?php echo $row["id"]; ?>,
		"name"           : "<?php echo $row["name"]; ?>",
		"area_id"        : "<?php echo $row["area_id"]; ?>"
	}<?php
	$first = false;
}
$result->free();
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["area_id"])){
		die("Missing area_id argument");
	}
	$area_id = intval($_POST["area_id"]);
	if (!isset($_POST["name"])){
		die("Missing name argument");
	}
	$name = $db->real_escape_string($_POST["name"]);
error_log("Création d'un domaine : ".$name);
$sql = <<<SQL
	insert into domain (name,area_id) values ('$name',$area_id)
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_REQUEST["id"])){
		die("Missing id argument");
	}
	$domain_id = $_REQUEST["id"];
// Vérifier qu'il n'y a pas de processus rattachés à ce domaine, sinon, on refuse
$sql = <<<SQL
	select id from process where domain_id = $domain_id
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
	if ($result->num_rows != 0){
		die("The domain contains process");
	}
$sql = <<<SQL
	delete from domain where id = $domain_id
SQL;
	if(!$result = $db->query($sql)){
	    	die('There was an error running the query [' . $db->error . ']');
	}
}
require("../db/disconnect.php");
?>
]}
