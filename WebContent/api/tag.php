<?php
header("Content-Type: application/json");
require("../dao/dao.php");
$dao->connect();
$db = $dao->getDB();
$sql = <<<SQL
    SELECT * from tag
SQL;
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "tags" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    : <?php echo $row["id"]; ?>,
		"value" : "<?php echo $row["value"]; ?>"
	}<?php
	$first = false;
}
$result->free();
$dao->disconnect();
?>
]}