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
                die();
                return;
            }
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
                $dao->addDocument($id,$document_id);
            }
        } else {
            // Element update
            
        }
    } else {
        // Insert
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
$dao->disconnect();
?>