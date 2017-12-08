<?php
header("Content-Type: application/json");
require("../dao/dao.php");
$dao->connect();
$db = $dao->getDB();
$sql = <<<SQL
    SELECT area.* from area
SQL;
if (isset($_GET["view"])){
	$sql .= " INNER JOIN view ON view.id = area.view_id where view.name = '".$_GET["view"]."'";
}
$sql .= " order by area.name";
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "areas" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    	: <?php echo $row["id"]; ?>,
		"code" 		: "<?php echo $row["code"]; ?>",
		"name" 		: "<?php echo $row["name"]; ?>",
		"position" 	: "<?php echo $row["position"]; ?>",
		"parent_id" 	: "<?php echo $row["parent_id"]; ?>",
		"view_id" 	: "<?php echo $row["view_id"]; ?>",
		"display" 	: "<?php echo $row["display"]; ?>"
	}<?php
	$first = false;
}
$result->free();
$dao->disconnect();
?>
]}