<?php
/**
 * Dao accédant à la base de donnée spécifique graf 
 */
class DBDao {
    // déclaration d'une propriété
    private $db = null;
    public function connect(){
        global $configuration;
        // Connection à la BDD
        $this->db = new mysqli($configuration->db->host, $configuration->db->user, $configuration->db->password, $configuration->db->instance);
        if (!$this->db) {
            displayErrorAndDie('Impossible de se connecter : ' . $this->db->connect_error);
        }
    }
    // déclaration des méthodes
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
    public function createDomain($name, $area_id){
        error_log("Création d'un domaine : ".$name);
        $name = $this->db->real_escape_string($name);
        $sql = "insert into domain (name,area_id) values ('$name',$area_id)";
        if(!$result = $this->db->query($sql)){
            die('There was an error running the query [' . $this->db->error . ']');
        }
    }
    public function deleteDomain(){
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