<?php
require("../dao/dao.php");
$dao->connect();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	if (isset($_GET["id"])){
		if (isset($_GET['content'])){
			$content = $dao->getDocumentContent($_GET["id"]);
			echo $content;
			return;
		} else {
			$query = array();
			$query['id'] = $_GET['id'];
			$documents = $dao->getDocuments((object)$query);
		}
	} else {
		$query = array();
		if (isset($_GET['type'])){
			$query['documentType'] = $_GET['type'];
		}
		if (isset($_GET['itemId'])){
			$query['itemId'] = $_GET['itemId'];
        }
        if (isset($_GET['name'])){
        	$query['name'] = $_GET['name'];
        }
        $documents = $dao->getDocuments((object)$query);
	}
?>
{ "documents" : [
<?php
$first = true;
foreach ($documents as $document){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    	: "<?php echo $document->id; ?>",
		"name" 		: "<?php echo $document->name; ?>",
		"type" 		: "<?php echo $document->type; ?>"
	}<?php
	$first = false;
}?>
]}<?php
/** METHOD POST **/
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["id"])){
        // Update
        $id = $_POST["id"];
        if (isset($_POST["document"])){
            $dao->updateDocument($id,$_POST["document"]);
        }
    } else {
    	if (!isset($_POST["type"])){
    		die("Missing type argument");
    	}
    	$type = $_POST["type"];
    	if (!isset($_POST["itemId"])){
    		die("Missing itemId argument");
    	}
    	$itemId = $_POST["itemId"];
    	// creation du document
    	$document_id = $dao->createDocument("Document",$type,$_POST["document"]);
    	$dao->addItemDocument($itemId,$document_id);
    }
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){
		die("Missing id argument");
	}
	$dao->deleteDocument($_GET["id"]);
}
$dao->disconnect();
?>