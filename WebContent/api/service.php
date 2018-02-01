<?php
header("Content-Type: application/json");
require("../dao/dao.php");
$dao->connect();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $services = array();
    if (isset($_GET["id"])){
        $result = $dao->getItemById($_GET["id"]);
        if ($result != null){
            $services[] = $result;
        }
    } else if (isset($_GET["domain_id"])){
        $services = $dao->getRelatedItems($_GET["domain_id"],"service","down");
    } else {
        $services = $dao->getItemsByCategory("service");
    }?>
{ "services" : [<?php
    $first = true;
    foreach ($services as $service){
        if ($first != true) {
            echo ",";
        }
        $first = false;
        ?>
        {	"id"        : "<?php echo $service->id; ?>",
			"name"      : "<?php echo $service->name; ?>"
		}<?php
    }?>
]}
<?php
/*$sql = <<<SQL
    SELECT service.*, instance.id as instance_id, instance.name as instance_name, environment.id as environment_id, environment.name as environment_name, environment.code as environment_code from service
    LEFT OUTER JOIN instance ON service.id = instance.service_id
    LEFT OUTER JOIN environment ON instance.environment_id = environment.id
SQL;

$sql .= " ORDER by service.name, service.id";
if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}?>
{ "services" : [
<?php
$first = true;
$first_instance = true;
$current_service_id = -1;
while($row = $result->fetch_assoc()){
	$service_id = $row["id"];
	if ($current_service_id != $service_id){
		$current_service_id = $service_id;
		$first_instance = true;
		if ($first != true) {?>
					]
		},
<?php		}?>
		{
			"id"        : <?php echo $service_id; ?>,
			"name"      : "<?php echo $row["name"]; ?>",
			"code"      : "<?php echo $row["code"]; ?>",
			"instances" : [
<?php	}
	if (isset($row["instance_id"])){
		if ($first_instance != true) {
			?>,
<?php
		}
		$first_instance = false;
?>							{
								"id"   : "<?php echo $row["instance_id"]; ?>",
								"name" : "<?php echo $row["instance_name"]; ?>",
								"environment" : { "id" : "<?php echo $row["environment_id"]; ?>", "code" : "<?php echo $row["environment_code"]; ?>", "name" : "<?php echo $row["environment_name"]; ?>"}
							}<?php
	}
	$first = false;
}
$result->free();
if ($first != true) {?>
					]
		}
<?php } ?>
]}
<?php*/
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["domain_id"])){
		die("Missing domain_id argument");
	}
	$domain_id = $_POST["domain_id"];
	if (!isset($_POST["code"])){
		die("Missing code argument");
	}
	$code = $_POST["code"];
	if (!isset($_POST["name"])){
		die("Missing code argument");
	}
	$name = $_POST["name"];
error_log("Création d'un service : ".$name." domaine ".$domain_id);
    $serviceId = $dao->createItem("Service",$code,$name,$domain_id);
    $dao->addSubItem($domain_id,$serviceId);
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){
		die("Missing id argument");
	}
	$id = intval($_GET["id"]);
    $dao->deleteService($id);
}
$dao->disconnect();
?>