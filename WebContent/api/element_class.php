<?php
require("../dao/dao.php");
$dao->connect();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
header("Content-Type: application/json");
$categories = $dao->getItemCategories();
?>
{ "categories" : [
<?php
$first = true;
foreach($categories as $category){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    	: "<?php echo $category->id; ?>",
		"name" 		: "<?php echo $category->name; ?>",
		"classes" 	: [<?php
            $firstclass = true;
            foreach($category->classes as $class){
                if ($firstclass != true) {
                    echo ",\n";
                }?>
                	{
                		"id" : "<?php echo $class->id; ?>",
						"name" : "<?php echo $class->name; ?>",
						"abstract"	: "<?php echo ($class->abstract?"true":"false"); ?>"
                	}
                <?php
                $firstclass = false;
			}
		?>]
	}<?php
	$first = false;
}?>
]}<?php
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = $dao->getDB();
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
$dao->disconnect();
?>