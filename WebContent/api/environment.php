<?php
header("Content-Type: application/json");
require("../dao/dao.php");
$dao->connect();?>
{ "environments" : [
    
]}
<?php


return;



$db = $dao->getDB();
$sql = <<<SQL
    SELECT * from environment
SQL;
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "environments" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    : <?php echo $row["id"]; ?>,
		"name" : "<?php echo $row["name"]; ?>"
	}<?php
	$first = false;
}
$result->free();
$dao->disconnect();
?>
]}
