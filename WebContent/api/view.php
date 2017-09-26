<?php
header("Content-Type: application/json");
require("../db/connect.php");
$sql = <<<SQL
    SELECT * from view
SQL;
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "views" : [
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
require("../db/disconnect.php");
?>
]}
