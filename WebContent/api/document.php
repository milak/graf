<?php
require("../api/dao.php");
$dao = getDAO("documents");
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
        if (isset($_POST["itemId"])){
        	$itemId = $_POST["itemId"];
        	$dao->addItemDocument($itemId,$id);
        } else {
	        if (!isset($_POST["name"])){
	        	die("Missing name argument");
	        }
	        $name = $_POST["name"];
	        if (!isset($_POST["type"])){
	        	die("Missing type argument");
	        }
	        $type = $_POST["type"];
	        if (isset($_POST["content"])){
	            $dao->updateDocument($id,$name,$type,$_POST["content"]);
	        } else {
	        	$dao->updateDocument($id,$name,$type,null);
	        }
        }
?>{
   "code"		: 0,
   "message"	: "Document updated",
   "objects"	: [{
         "code"		: 0,
         "message"	: "updated",
         "class"	: "Document",
         "id"		: "<?php echo $id;?>",
         "fields": {
            "id"	: "<?php echo $id;?>"
         }
      }
   ]
}<?php
    } else {
    	if (!isset($_POST["name"])){
    		die("Missing name argument");
    	}
    	$name = $_POST["name"];
    	if (!isset($_POST["type"])){
    		die("Missing type argument");
    	}
    	$type = $_POST["type"];
    	// creation du document
    	try {
	    	$document_id = $dao->createDocument($name,$type," ");
	    	if (isset($_POST["itemId"])){
	    		$itemId = $_POST["itemId"];
	    		$dao->addItemDocument($itemId,$document_id);
	    	}
    	} catch (Exception $exception){?>
{
   "code"		: 12,
   "message"	: "<?php echo $exception->getMessage()?>",
   "objects"	: []
}
<?php  			return;
    		}
?>{
   "code"		: 0,
   "message"	: "Document created",
   "objects"	: [{
         "code"		: 0,
         "message"	: "created",
         "class"	: "Document",
         "id"		: "<?php echo $document_id;?>",
         "fields": {
            "id"	: "<?php echo $document_id;?>",
            "name"	: "<?php echo $name;?>"
         }
      }
   ]
}<?php
    }
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){
?>{
   "code"		: 12,
   "message"	: "Missing id argument",
   "objects"	: []
}<?php
		return;
	}
	if (isset($_GET["itemId"])){
		$itemId = $_GET["itemId"];
		$dao->deleteItemDocument($itemId,$_GET["id"]);
?>{
   "code"		: 0,
   "message"	: "Link to item deleted",
   "objects"	: []
}<?php
	} else {
		$dao->deleteDocument($_GET["id"]);
?>{
   "code"		: 0,
   "message"	: "Document deleted",
   "objects"	: []
}<?php
	}
}
$dao->disconnect();
?>