<?php
/**
 * Dao accédant aux données de itop
 */
class ITopDao {
    // déclaration d'une propriété
    private $organisation;
    private $url;
    private $auth_user;
    private $auth_pwd;
    private $version;
    public function connect(){
        global $configuration;
        $this->organisation = $configuration->itop->organisation;
        $this->url          = $configuration->itop->url;
        $this->auth_user    = $configuration->itop->auth_user;
        $this->auth_pwd     = $configuration->itop->auth_pwd;
        $this->version      = $configuration->itop->version;
    }
    /**
     * Helper to execute an HTTP POST request
     * Source: http://netevil.org/blog/2006/nov/http-post-from-php-without-curl
     *         originaly named after do_post_request
     * $sOptionnalHeaders is a string containing additional HTTP headers that you would like to send in your request.
     */
    private function DoPostRequest($jsonData, $sOptionnalHeaders = null) {
        $sUrl = "http://localhost/itop/web/webservices/rest.php?version=1.3";
        $aData = array();
        $aData['auth_user'] = 'admin';
        $aData['auth_pwd'] = 'admin';
        $aData['json_data'] = $jsonData;
        $sData = http_build_query($aData);
        $aParams = array('http' => array(
                'method' => 'POST',
                'content' => $sData,
                'header'=> "Content-type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($sData)."\r\n",
        ));
        if ($sOptionnalHeaders !== null) {
            $aParams['http']['header'] .= $sOptionnalHeaders;
        }
        $ctx = stream_context_create($aParams);
            
        $fp = @fopen($sUrl, 'rb', false, $ctx);
        if (!$fp) {
                global $php_errormsg;
                if (isset($php_errormsg))
                {
                    throw new Exception("Problem with $sUrl, $php_errormsg");
                }
                else
                {
                    throw new Exception("Problem with $sUrl");
                }
         }
         $response = @stream_get_contents($fp);
         if ($response === false) {
                throw new Exception("Problem reading data from $sUrl, $php_errormsg");
         }
         return $response;
    }
    // déclaration des méthodes
    public function getDomains() {
        $clause = 'SELECT Group WHERE type="businessDomain"';
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class' => 'Group',
            'key' => $clause,
            'output_fields' => 'id, name, friendlyname, status, description, type, parent_id, parent_name, ci_list, obsolescence_flag',
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        if ($response->code != 0) {
            die("Error : ".$response->message);
        }
        $result = array();
        foreach ($response->objects as $object){
            $row = new stdClass();
            $row->id = $object->key;
            $row->name = $object->fields->name;
            $row->area_id = $object->fields->description;
            $result[] = $row;
        }
        return $result;
    }
    public function getDomainById($id){
        $clause = 'SELECT Group WHERE type="businessDomain"';
            $clause .= " AND id=".$id;
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class' => 'Group',
            'key' => $clause,
            'output_fields' => 'id, name, friendlyname, status, description, type, parent_id, parent_name, ci_list, obsolescence_flag',
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        if ($response->code != 0) {
            die("Error : ".$response->message);
        }
        $result = array();
        foreach ($response->objects as $object){
            $row = new stdClass();
            $row->id = $object->key;
            $row->name = $object->fields->name;
            $row->area_id = $object->fields->description;
            $result[] = $row;
        }
        return $result;
    }
    public function createDomain($name,$area_id){
        error_log("Création d'un domaine : ".$name);
        $jsonData = json_encode(array(
            'operation' => 'core/create', // operation code
            'comment' => 'Inserted from GRAF',
            'class' => 'Group',
            'output_fields' => 'id, friendlyname', // list of fields to show in the results (* or a,b,c)
            // Values for the object to create
            'fields' => array(
                'org_id' => "SELECT Organization WHERE name = '$organisation'",
                'name' => $name,
                'type' => 'businessDomain',
                'status' => 'production',
                'description' => "area_id=$area_id"
            ),
        ));
        $response = $this->DoPostRequest($jsonData);
        if ($response->code != 0) {
            die("Error : ".$response->message);
        }
    }
    public function deleteDomain($id){
        $jsonData = json_encode(array(
            'operation' => 'core/delete', // operation code
            'comment' => 'Delete from GRAF',
            'class' => 'Group',
            'key' => $id,
            'simulate' => false,
        ));
        $response = $this->DoPostRequest($jsonData);
        error_log($response);
    }
    public function disconnect(){
    }
}
$dao = new ITopDao();
?>