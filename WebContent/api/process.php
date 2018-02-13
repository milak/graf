<?php
require("../dao/dao.php");
$dao->connect();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET["id"])){
        if (isset($_GET["asXML"])){
            header("Content-Type: text/xml");
            $xml = $dao->getItemDocument($_GET["id"],"BPMN");
            echo $xml;
            return;
        } else {
            $processes = $dao->getItems((object)['id'=>$_GET['id']]);
        }
    } else if (isset($_GET["domain_id"])){
        $processes = $dao->getRelatedItems($_GET["domain_id"],"process","down");
    } else {
    	$processes = $dao->getItems((object)['category'=>'process']);
    }
    header("Content-Type: application/json");?>
{ "process" : [
<?php
    $first = true;
    foreach($processes as $process){
	   if ($first != true) {
            echo ",\n";
	   }?>
	{
		"id"             : "<?php echo $process->id; ?>",
		"name"           : "<?php echo $process->name; ?>"
	}<?php
	$first = false;
}
echo "]}";
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST["name"])){
		die("Missing name argument");
	}
	$name = $_POST["name"];
	if (strlen($name) < 4){
		die("Name argument too short");
	}
	if (!isset($_POST["description"])){
		die("Missing description argument");
	}
	$description = $_POST["description"];
	if (strlen($description) < 5){
		die("Description argument too short");
	}
	if (!isset($_POST["domain_id"])){
		die("Missing domain_id argument");
	}
	$domain_id = $_POST["domain_id"];
	error_log("Création d'un processus : ".$name);
	$id = $dao->createItem("Process",$name,$name,$description);
	$dao->addSubItem($domain_id,$id);
	$documentid = $dao->createDocument("BPMN document of process ".$name, "BPMN", DEFAULT_PROCESS_CONTENT);
	$dao->addItemDocument($id,$documentid);
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_REQUEST["id"])){
		die("Missing id argument");
	}
	$dao->deleteItem($_REQUEST["id"]);
}
$dao->disconnect();
?>