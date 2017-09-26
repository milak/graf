<?php
header("Content-Type: application/json");
require("start.php");
$sql = <<<SQL
    SELECT * from service
SQL;
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "services" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    : <?php echo $row["id"]; ?>,
		"name"  : "<?php echo $row["name"]; ?>"
	}<?php
	$first = false;
}
$result->free();
require("stop.php");
?>
]}
