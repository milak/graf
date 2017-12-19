<?php
header("Content-Type: application/json");
require("../dao/dao.php");
$dao->connect();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET["id"])){
        $domains = array($dao->getDomainById($_GET["id"]));
    } else {
        $domains = $dao->getDomains();
    }?>
{ "domains" : [
<?php
    $first = true;
    foreach($domains as $domain){
	   if ($first != true) {
		  echo ",\n";
	   }?>
	{
		"id"             : <?php echo $domain->id; ?>,
		"name"           : "<?php echo $domain->name; ?>",
		"area_id"        : "<?php echo $domain->area_id; ?>"
	}<?php
	   $first = false;
    }?>
]}
<?php
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["area_id"])){
		die("Missing area_id argument");
	}
	$area_id = $_POST["area_id"];
	if (!isset($_POST["name"])){
		die("Missing name argument");
	}
	$name = $_POST["name"];
    $dao->createDomain($name,$area_id);
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_REQUEST["id"])){
		die("Missing id argument");
	}
	$domain_id = $_REQUEST["id"];
    $dao->deleteDomain($domain_id);
}
$dao->disconnect();
?>