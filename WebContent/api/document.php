<?php
require("../dao/dao.php");
$dao->connect();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	if (isset($_GET["id"])){
		$content = $dao->getDocumentContent($_GET["id"]);
		echo $content;
		return;
	} else {
		$type = '*';
		if (isset($_GET["type"])){
			$type = $_GET["type"];
		}
		if (isset($_GET["itemId"])){
			$documents = $dao->getItemDocuments($_GET["itemId"],$type);
        } else {
        	// pour le moment, on ne liste pas tous les documents, on doit passer par un item
        	die("Missing itemId argument");
        	return;
        }
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