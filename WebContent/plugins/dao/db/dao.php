<?php
/**
 * Dao accédant à la base de donnée spécifique graf 
 */
class DBDao implements IDAO {
    // déclaration d'une propriété
    private $db = null;
    public $error = null;
    /**
     * prend en compte les parametres de connection et effectue un test de connection
     * @return boolean true si la connection s'est bien passée, false sinon. Dans ce cas, error contient le message d'erreur
     */
    public function connect(){
        global $configuration;
        $connector = null;
        foreach ($configuration->connectors as $index => $con){
        	if ($con->name == "db"){
        		$connector = $con;
        		break;
        	}
        }
        if ($connector == null){
        	error_log("No connector found width 'db' as name");
        	$result = false;
        } else {
	        // Connection à la BDD
        	$this->db = new mysqli($connector->host, $connector->login, $connector->password, $connector->instance);
	        if ($this->db->connect_errno) {
	            $result = false;
	            $this->error = $this->db->connect_error;
	        } else {
	            $result = true;
	        }
        }
        return $result;
    }
    public function getDomains() {
        $sql = "SELECT domain.* from domain order by name";
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = array();
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id = $dbrow["id"];
            $row->name = $dbrow["name"];
            $row->area_id = $dbrow["area_id"];
            $result[] = $row;
        }
        $dbresult->free();
        return $result;
    }
    public function getDomainById($id){
        $sql = "SELECT domain.* from domain where domain.id = ".$id;
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = null;
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id = $dbrow["id"];
            $row->name = $dbrow["name"];
            $row->area_id = $dbrow["area_id"];
			$result = $row;
			break;
	    }
        $dbresult->free();
        return $result;
    }
    public function createDomain($name, $area_id){
        error_log("Création d'un domaine : ".$name.",".$area_id);
        $name = $this->db->real_escape_string($name);
        $sql = "insert into domain (name,area_id) values ('$name',$area_id)";
        if(!$result = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
    }
    public function deleteDomain($domain_id){
        // Vérifier qu'il n'y a pas de processus rattachés à ce domaine, sinon, on refuse
        $sql = "select id from process where domain_id = $domain_id";
        if(!$result = $db->query($sql)){
            die('There was an error running the query [' . $db->error . ']');
        }
        if ($result->num_rows != 0){
            die("The domain contains process");
        }
        $sql = "delete from domain where id = $domain_id";
        if(!$result = $db->query($sql)){
            die('There was an error running the query [' . $db->error . ']');
        }
    }
    public function getBusinessProcesses(){
       $sql = <<<SQL
    SELECT process.*, domain.name as domain_name from process
    INNER JOIN domain ON domain.id = process.domain_id
SQL;
       if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
       }
       $result = array();
       while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
		    $row->id          = $dbrow["id"];
		    $row->name        = $dbrow["name"];
		    $row->domain_id   = $dbrow["domain_id"];
		    $row->domain_name = $dbrow["domain_name"];
		    $result[] = $row;
	   }
	   $dbresult->free();
       return $result;
    }
    public function getBusinessProcessById($id){
        $sql = <<<SQL
    SELECT process.*, domain.name as domain_name from process
    INNER JOIN domain ON domain.id = process.domain_id
    where process.id = $id
SQL;
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = array();
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id          = $dbrow["id"];
            $row->name        = $dbrow["name"];
            $row->domain_id   = $dbrow["domain_id"];
            $row->domain_name = $dbrow["domain_name"];
            $result[] = $row;
        }
        $dbresult->free();
        return $result;
    }
    public function getBusinessProcessByDomainId($domainId){
        $sql = <<<SQL
    SELECT process.*, domain.name as domain_name from process
    INNER JOIN domain ON domain.id = process.domain_id
    where domain.id = $domainId
SQL;
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = array();
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id          = $dbrow["id"];
            $row->name        = $dbrow["name"];
            $row->domain_id   = $dbrow["domain_id"];
            $row->domain_name = $dbrow["domain_name"];
            $result[] = $row;
        }
        $dbresult->free();
        return $result;
    }
    public function getBusinessProcessDocument($id){
        // Chercher le étapes
        $sql = <<<SQL
    SELECT process_step.id as step_id, process_step.name as step_name, step_type.name as step_type_name, process_step.sub_process_id
    FROM process_step
    INNER JOIN step_type ON process_step.step_type_id = step_type.id
    WHERE process_step.process_id=$id
SQL;
        if(!$result = $this->db->query($sql)){
            displayErrorAndDie('There was an error running the query [' . $this->db->error . ']');
        }
        $steps = array();
        // Charger toutes les étapes
        while($row = $result->fetch_assoc()){
            $step 				= new stdClass();
            $step->id 			= $row["step_id"];
            $step->type_name 	= $row["step_type_name"];
            $step->name 		= $row["step_name"];
            $step->links 		= array();
            $steps[$step->id]	= $step;
        }
        $result->free();
        $sql = <<<SQL
    SELECT step_link.*
    FROM step_link
    WHERE process_id=$id
    ORDER BY process_id
SQL;
        if(!$result = $this->db->query($sql)){
            displayErrorAndDie('There was an error running the query [' . $this->db->error . ']');
        }
        // Charger tous les liens et associer chaque step
        while($row = $result->fetch_assoc()){
            $from_id 	= $row["from_step_id"];
            $to_id 		= $row["to_step_id"];
            $from_step 	= $steps[$from_id];
            $to_step	= $steps[$to_id];
            $link 		= new stdClass();
            $link->to 	= $to_step;
            $link->label	= $row["label"];
            $from_step->links[] = $link;
        }
        $result->free();
        return $steps;
    }
    public function getBusinessProcessDocumentAsXML($id){
        return "";
    }
    public function createBusinessProcess($name,$description,$domain_id){
        $name = $this->db->real_escape_string($name);
        if (strlen($name) < 4){
            die("Name argument too short");
        }
        $description = $this->db->real_escape_string($description);
        if (strlen($description) < 5){
            die("Description argument too short");
        }
        error_log("Création d'un processus : ".$name);
        $sql = <<<SQL
	insert into process (name,description,domain_id) values ('$name','$description',$domain_id)
SQL;
        if(!$result = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $process_id = $this->db->insert_id;
        $sql = <<<SQL
	insert into process_step (process_id,name,step_type_id) values ($process_id,'start',(select id from step_type where name = "START"))
SQL;
        if(!$result = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
    }
    public function deleteBusinessProcess($id){
        $sql = <<<SQL
	delete from step_link where process_id = $process_id
SQL;
        if(!$result = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $sql = <<<SQL
	delete from process_step where process_id = $process_id
SQL;
        if(!$result = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $sql = <<<SQL
	delete from process where id = $process_id
SQL;
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $dbresult->free();
    }
    public function getViews(){
        $sql = <<<SQL
    SELECT * from view
SQL;
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = array();
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id    = $dbrow["id"];
		    $row->name  = $dbrow["name"];
		    $result[]   = $row;
        }
        $dbresult->free();
        return $result;
    }
    // ********************
    // Chargement des zones
    // ********************
    function getViewByName($view_name){
        $sql = <<<SQL
    SELECT text from view
    where name = '$view_name'
SQL;
        if (!$result = $this->db->query($sql)){
            displayErrorAndDie('There was an error running the query [' . $db->error . ']');
        }
        $text = "";
        while($row = $result->fetch_assoc()){
            $text = $row["text"];
            break;
        }
        $result->free();
        $text = json_decode($text, JSON_UNESCAPED_UNICODE);
        return parseViewToArray($text);
    }
    public function getServices(){
        $sql = <<<SQL
   SELECT * from service
   ORDER BY name
SQL;
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = array();
        // Charger tous les services
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id 		= $dbrow["id"];
            $row->name 		= $dbrow["name"];
            $row->code 		= $dbrow["code"];
            $result[] = $row;
        }
        $dbresult->free();
        return $result;
    }
    public function getServiceById($id){
        $sql = <<<SQL
   SELECT * from service
   WHERE id = $id
SQL;
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = null;
        // Charger tous les services
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id 		= $dbrow["id"];
            $row->name 		= $dbrow["name"];
            $row->code 		= $dbrow["code"];
            $result = $row;
            break;
        }
        $dbresult->free();
        return $result;
    }
    public function getServicesByDomainId($id){
        $sql = <<<SQL
   SELECT * from service
   WHERE domain_id = $id
SQL;
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = array();
        // Charger tous les services
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id 		= $dbrow["id"];
            $row->name 		= $dbrow["name"];
            $row->code 		= $dbrow["code"];
            $result[] = $row;
        }
        $dbresult->free();
        return $result;
    }
    public function createService($code,$name,$domain_id){
        $code = $this->db->real_escape_string($code);
        $name = $this->db->real_escape_string($name);
        $sql = <<<SQL
	insert into service (code,name,domain_id) values ('$code','$name',$domain_id)
SQL;
        if(!$result = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
    }
    public function deleteService($id){
        $sql = <<<SQL
	delete from service where id = $id
SQL;
        if(!$result = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
    }
    public function createItem($className,$name,$code,$description){
        $sql = <<<SQL
            insert into element (name,element_class_id) values ('$name',$class_id)
SQL;
        $domain_id = intval($_POST["domain_id"]);
        $sql = <<<SQL
    insert into element (name,domain_id,element_class_id) values ('$name',$domain_id,$class_id)
SQL;
        if(!$result = $db->query($sql)){
            die('There was an error running the query [' . $db->error . ']');
        }
    }
    public function getItemCategories(){
        $sql = <<<SQL
    SELECT element_class.*, element_category.id as category_id, element_category.name as category_name from element_class
	INNER JOIN element_category ON element_class.element_category_id 		= element_category.id
SQL;
        if (isset($_GET["category_name"])){
            $sql .= " where element_category.name = '".$_GET["category_name"]."'";
        }
        $sql .= "ORDER BY element_category.name, element_class.name";
        if(!$result = $db->query($sql)){
            die('There was an error running the query [' . $db->error . ']');
        }
        
    }
    public function getItemsByCategory($category){
        $sql = <<<SQL
    SELECT element.*, element_class.id as class_id, element_class.name as class_name, element_category.id as category_id, element_category.name as category_name  from element
	INNER JOIN element_class ON element.element_class_id 					= element_class.id
	INNER JOIN element_category ON element_class.element_category_id 		= element_category.id
	LEFT OUTER JOIN domain 		ON element.domain_id 		= domain.id
    where element_category.name = '$category'
SQL;
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = array();
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id = $dbrow["id"];
            $row->name = $dbrow["name"];
            $row->domain_id = $dbrow["domain_id"];
            $row->class = new stdClass();
            $row->class->id = $dbrow["class_id"];
            $row->class->name = $dbrow["class_name"];
            $row->category = new stdClass();
            $row->category->id = $dbrow["category_id"];
            $row->category->name = $dbrow["category_name"];
            $result[] = $row;
	   }
	   $dbresult->free();
	   return $result;
    }
    public function getItemsByClass($class_id){
        $sql = <<<SQL
    SELECT element.*, element_class.id as class_id, element_class.name as class_name, element_category.id as category_id, element_category.name as category_name  from element
	INNER JOIN element_class ON element.element_class_id 					= element_class.id
	INNER JOIN element_category ON element_class.element_category_id 		= element_category.id
	LEFT OUTER JOIN domain 		ON element.domain_id 		= domain.id
    where element_class.id = $class_id
SQL;
        if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = array();
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id = $dbrow["id"];
            $row->name = $dbrow["name"];
            $row->domain_id = $dbrow["domain_id"];
            $row->class = new stdClass();
            $row->class->id = $dbrow["class_id"];
            $row->class->name = $dbrow["class_name"];
            $row->category = new stdClass();
            $row->category->id = $dbrow["category_id"];
            $row->category->name = $dbrow["category_name"];
            $result[] = $row;
        }
        $dbresult->free();
        return $result;
    }
    public function getItemById($id){
    $sql = <<<SQL
    SELECT element.*, element_class.id as class_id, element_class.name as class_name, element_category.id as category_id, element_category.name as category_name  from element
	INNER JOIN element_class ON element.element_class_id 					= element_class.id
	INNER JOIN element_category ON element_class.element_category_id 		= element_category.id
	LEFT OUTER JOIN domain 		ON element.domain_id 		= domain.id
    where element.id = $id
SQL;
        if(!$result = $db->query($sql)){
            die('There was an error running the query [' . $db->error . ']');
        }
        $result = null;
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id = $dbrow["id"];
            $row->name = $dbrow["name"];
            $row->domain_id = $dbrow["domain_id"];
            $row->class = new stdClass();
            $row->class->id = $dbrow["class_id"];
            $row->class->name = $dbrow["class_name"];
            $row->category = new stdClass();
            $row->category->id = $dbrow["category_id"];
            $row->category->name = $dbrow["category_name"];
            $result = $row;
            break;
        }
        $dbresult->free();
        return $result;
    }
    public function getItems($query){
        $sql = <<<SQL
    SELECT element.*, element_class.id as class_id, element_class.name as class_name, element_category.id as category_id, element_category.name as category_name  from element
	INNER JOIN element_class ON element.element_class_id 					= element_class.id
	INNER JOIN element_category ON element_class.element_category_id 		= element_category.id
	LEFT OUTER JOIN domain 		ON element.domain_id 		= domain.id
SQL;
		if(!$dbresult = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
        $result = array();
        while($dbrow = $dbresult->fetch_assoc()){
            $row = new stdClass();
            $row->id = $dbrow["id"];
            $row->name = $dbrow["name"];
            $row->domain_id = $dbrow["domain_id"];
            $row->class = new stdClass();
            $row->class->id = $dbrow["class_id"];
            $row->class->name = $dbrow["class_name"];
            $row->category = new stdClass();
            $row->category->id = $dbrow["category_id"];
            $row->category->name = $dbrow["category_name"];
            $result[] = $row;
        }
        $dbresult->free();
        return $result;
    }
    public function getItemsByDomain($domainId){
	return array();
    }
    public function getRelatedItems($itemId){
	return array();
    }
    public function getDocuments($query){
    	return null;
    }
    public function deleteItem($itemId){
        $sql = <<<SQL
    delete from element where id = $itemId
SQL;
        if(!$result = $db->query($sql)){
            die('There was an error running the query [' . $db->error . ']');
        }
    }
    public function query($sql) {
        return $this->db->query($sql);
    }
    public function getDB() {
        return $this->db;
    }
    public function disconnect(){
        // Déconnection à la BDD
        $this->db->close();
    }
}
$dao = new DBDao();
?>