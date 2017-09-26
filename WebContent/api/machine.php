<?php
header("Content-Type: application/json");
require("start.php");
$sql = <<<SQL
    SELECT * from machine
SQL;
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "machines" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    : <?php echo $row["id"]; ?>,
		"fqdn"  : "<?php echo $row["fqdn"]; ?>",
		"alias" : "<?php echo $row["alias"]; ?>"
	}<?php
	$first = false;
}
$result->free();
require("stop.php");
?>
]}
