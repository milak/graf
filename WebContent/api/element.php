<?php
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");
$sql = <<<SQL
    SELECT element.*, element_class.id as class_id, element_class.name as class_name, element_category.id as category_id, element_category.name as category_name  from element
	INNER JOIN element_class ON element.element_class_id 					= element_class.id
	INNER JOIN element_category ON element_class.element_category_id 		= element_category.id
	LEFT OUTER JOIN domain 		ON element.domain_id 		= domain.id
SQL;
if (isset($_GET["id"])){
	$sql .= " where element.id = ".$_GET["id"];
}
if (isset($_GET["category_name"])){
	$sql .= " where element_category.name = '".$_GET["category_name"]."'";
}
if (isset($_GET["class_id"])){
	$sql .= " where element_class.id = ".$_GET["class_id"];
}
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "elements" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    	: <?php echo $row["id"]; ?>,
		"name" 		: "<?php echo $row["name"]; ?>",
		"domain_id" : "<?php echo $row["domain_id"]; ?>",
		"class" 	: { "id" : "<?php echo $row["class_id"]; ?>", "name" : "<?php echo $row["class_name"]; ?>"},
		"category" 	: { "id" : "<?php echo $row["category_id"]; ?>", "name" : "<?php echo $row["category_name"]; ?>"}
	}<?php
	$first = false;
}?>
]}<?php
$result->free();
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["name"])){
		die("Missing name argument");
	}
	$name = $db->real_escape_string($_POST["name"]);
	if (!isset($_POST["class_id"])){
		die("Missing class_id argument");
	}
	$class_id = intval($_POST["class_id"]);
	if (!isset($_POST["domain_id"])){
$sql = <<<SQL
    insert into element (name,element_class_id) values ('$name',$class_id)
SQL;
	} else {
		$domain_id = intval($_POST["domain_id"]);
$sql = <<<SQL
    insert into element (name,domain_id,element_class_id) values ('$name',$domain_id,$class_id)
SQL;
	}
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){
		die("Missing id argument");
	}
	$id = intval($_GET["id"]);
	error_log("Removing element ".$id);
$sql = <<<SQL
    delete from element where id = $id
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
}
require("../db/disconnect.php");
?>
