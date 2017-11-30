<?php
require("../db/connect.php");
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");
$sql = <<<SQL
    SELECT element_class.*, element_category.id as category_id, element_category.name as category_name from element_class
	INNER JOIN element_category ON element_class.element_category_id 		= element_category.id
SQL;
if (isset($_GET["category_name"])){
	$sql .= " where element_category.name = '".$_GET["category_name"]."'";
}
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}
$sql .= "ORDER BY element_category.name, element_class.name";
?>
{ "classes" : [
<?php
$first = true;
while($row = $result->fetch_assoc()){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    	: <?php echo $row["id"]; ?>,
		"name" 		: "<?php echo $row["name"]; ?>",
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
	if (!isset($_POST["category_id"])){
		die("Missing category_id argument");
	}
	$class_id = intval($_POST["category_id"]);
$sql = <<<SQL
    insert into element_class (name,element_category_id) values ('$name',$category_id)
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){
		die("Missing id argument");
	}
	$id = intval($_GET["id"]);
	error_log("Removing element_class ".$id);
$sql = <<<SQL
    delete from element_class where id = $id
SQL;
	if(!$result = $db->query($sql)){
    	die('There was an error running the query [' . $db->error . ']');
	}
}
require("../db/disconnect.php");
?>
