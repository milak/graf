<?php
require("../dao/dao.php");
$dao->connect();
/** METHOD GET **/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header("Content-Type: application/json");
    if (isset($_GET["category_name"])){
        $items = $dao->getItemsByCategory($_GET["category_name"]);
    } else if (isset($_GET["class_name"])){
        $items = $dao->getItemsByClass($_GET["class_name"]);
    } else if (isset($_GET["domain_id"])){
        $items = $dao->getItemsByDomainId($_GET["domain_id"]);
    } else if (isset($_GET["id"])){
        if (isset($_GET["document"])){
            if (!isset($_GET["type"])){
                die("Missing type argument");
            }
            $documents = $dao->getItemDocuments($_GET["id"],$_GET["type"]);
            if (count($documents) != 0){
                $document = $documents[0];
                $content = $dao->getDocumentContent($document->id);
                echo $content;
                return;
            } else {
                http_response_code(404);
                //die("introuvable");
                return;
            }
        } else if (isset($_GET["sub_items"])){
            $items = $dao->getSubItems($_GET["id"]);
        } else {    
            $items = array();
            $item = $dao->getItemById($_GET["id"]);
            if ($item != null) {
                $items[] = $item;
            }
        }
    } else {
        $items = $dao->getItems();
    }
?>
{ "elements" : [
<?php
$first = true;
foreach ($items as $item){
	if ($first != true) {
		echo ",\n";
	}?>
	{
		"id"    	: "<?php echo $item->id; ?>",
		"name" 		: "<?php echo $item->name; ?>",
		"class" 	: { "id" : "<?php echo $item->class->id; ?>", "name" : "<?php echo $item->class->name; ?>"},
		"category" 	: { "id" : "<?php echo $item->category->id; ?>", "name" : "<?php echo $item->category->name; ?>"}
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
            if (!isset($_POST["type"])){
                die("Missing type argument");
            }
            // Retrouver l'id du document, l'ideal serait que l'on fournisse l'id du document
            $documents = $dao->getItemDocuments($id,$_POST["type"]);
            if (count($documents) != 0){
                $document = $documents[0];
                $dao->updateDocument($document->id,$_POST["document"]);
                return;
            } else { // creation du document
                $document_id = $dao->createDocument("Document",$_POST["type"],$_POST["document"]);
                $dao->addItemDocument($id,$document_id);
            }
        } else if (isset($_POST["child_id"])){
            // Ajout d'un fils à id
            $dao->addSubItem($id,$_POST["child_id"]);
        } else {
            // Element update
        }
    } else {
        // Insert
        if (!isset($_POST["name"])){
            die("Missing name argument");
        }
        $name = $_POST["name"];
        if (!isset($_POST["class_name"])){
            die("Missing class_name argument");
        }
        $class_name = $_POST["class_name"];
        $dao->createItem($class_name,$name,$name,"");
        if (isset($_POST["domain_id"])){
            // ??
        }
    }
/** METHOD DELETE **/
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	if (!isset($_GET["id"])){
		die("Missing id argument");
	}
	$id = $_GET["id"];
    if (isset($_GET["child_id"])){
        // Ajout d'un fils à id
        $dao->removeSubItem($id,$_GET["child_id"]);
    } else {
        $dao->deleteItem($id);
    }
}
$dao->disconnect();
?>