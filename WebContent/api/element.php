<?php
require ("../dao/dao.php");
$dao->connect ();
/**
 * METHOD GET *
 */
if ($_SERVER ['REQUEST_METHOD'] === 'GET') {
	header ( "Content-Type: application/json" );
	if (isset ( $_GET ["document"] )) {
		if (! isset ( $_GET ["type"] )) {
			die ( "Missing type argument" );
		}
		if (! isset ( $_GET ["id"] )) {
			die ( "Missing id argument" );
		}
		$documents = $dao->getItemDocuments ( $_GET ["id"], $_GET ["type"] );
		if (count ( $documents ) != 0) {
			$document = $documents [0];
			$content = $dao->getDocumentContent ( $document->id );
			echo $content;
			return;
		} else {
			http_response_code ( 404 );
			// die("introuvable");
			return;
		}
	} else if (isset ( $_GET ["related_items"] )) {
		if (! isset ( $_GET ["id"] )) {
			die ( "Missing id argument" );
		}
		$direction = "down";
		if (isset ( $_GET ["direction"] )) {
			$direction = $_GET ["direction"];
		}
		$items = $dao->getRelatedItems ( $_GET ["id"], '*', $direction );
	} else {
		$query = array ();
		if (isset ( $_GET ["category_name"] )) {
			$query ['category'] = $_GET ["category_name"];
		}
		if (isset ( $_GET ["class_name"] )) {
			$query ['class'] = $_GET ["class_name"];
		}
		if (isset ( $_GET ["name"] )) {
			$query ['name'] = $_GET ["name"];
		}
		if (isset ( $_GET ["id"] )) {
			$query ['id'] = $_GET ["id"];
		}
		$items = $dao->getItems ( ( object ) $query );
	}
	?>
{
	"code" : 0,
	"message" : "Found <?php echo count($items);?>",
	"elements" : [
<?php
	$first = true;
	foreach ( $items as $item ) {
		if ($first != true) {
			echo ",\n";
		}
		?>
	{
		"id"    	: "<?php echo $item->id; ?>",
		"name" 		: "<?php echo $item->name; ?>",
		"class" 	: { "id" : "<?php echo $item->class->id; ?>", "name" : "<?php echo $item->class->name; ?>"},
		"category" 	: { "id" : "<?php echo $item->category->id; ?>", "name" : "<?php echo $item->category->name; ?>"},
		"properties": [<?php
		$firstProperty = true;
		foreach ( $item->properties as $key => $property ) {
			if ($firstProperty != true) {
				echo ",\n";
			}
			echo '{"key": "' . $key . '", "value" : "' . $property . '"}';
			$firstProperty = false;
		}
		?>]
	}<?php
		$first = false;
	}
	?>
]}<?php
/**
 * METHOD POST *
 */
} else if ($_SERVER ['REQUEST_METHOD'] === 'POST') {
	if (isset ( $_POST ["id"] )) {
		// Update
		$id = $_POST ["id"];
		if (isset ( $_POST ["document"] )) {
			if (! isset ( $_POST ["type"] )) {
				die ( "Missing type argument" );
			}
			// Retrouver l'id du document, l'ideal serait que l'on fournisse l'id du document
			$documents = $dao->getItemDocuments ( $id, $_POST ["type"] );
			if (count ( $documents ) != 0) {
				$document = $documents [0];
				$dao->updateDocument ( $document->id, $_POST ["document"] );
				return;
			} else { // creation du document
				$document_id = $dao->createDocument ( "Document", $_POST ["type"], $_POST ["document"] );
				$dao->addItemDocument ( $id, $document_id );
			}
		} else if (isset ( $_POST ["child_id"] )) {
			// Ajout d'un fils à id
			try {
				$dao->addSubItem ( $id, $_POST ["child_id"] );
				?>
				{
            		"code"		: 0,
            		"message"	: "Objects linked",
            		"objects"	: []
            	}
            	<?php
			} catch ( Exception $exception ) {
				?>
				{
            		"code"		: 12,
            		"message"	: "<?php echo $exception->getMessage()?>",
            		"objects"	: []
            	}
            	<?php
			}
		} else {
			// Element update
		}
	} else {
		// Insert
		if (! isset ( $_POST ["name"] )) {
			die ( "Missing name argument" );
		}
		$name = $_POST ["name"];
		if (! isset ( $_POST ["class_name"] )) {
			die ( "Missing class_name argument" );
		}
		$class_name = $_POST ["class_name"];
		$properties = array ();
		foreach ( $_POST as $key => $value ) {
			if ($key == "name") {
				continue;
			}
			if ($key == "class_name") {
				continue;
			}
			$properties [$key] = $value;
		}
		$id = $dao->createItem ( $class_name, $name, $name, "", $properties );
		?>
{
   "code"		: 0,
   "message"	: "Item created",
   "objects"	: [{
         "code"		: 0,
         "message"	: "created",
         "class"	: "Item",
         "id"		: "<?php echo $id;?>",
         "fields": {
            "id"	: "<?php echo $id;?>",
            "name"	: "<?php echo $name;?>"
         }
      }
   ]
}<?php
	}
/**
 * METHOD DELETE *
 */
} else if ($_SERVER ['REQUEST_METHOD'] === 'DELETE') {
	if (! isset ( $_GET ["id"] )) {
		die ( "Missing id argument" );
	}
	$id = $_GET ["id"];
	if (isset ( $_GET ["child_id"] )) {
		// Ajout d'un fils à id
		$dao->removeSubItem ( $id, $_GET ["child_id"] );
	} else {
		$dao->deleteItem ( $id );
	}
}
$dao->disconnect ();
?>