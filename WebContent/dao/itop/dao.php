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
                'header'=> "Content-type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($sData)."\r\n"
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
            'output_fields' => 'id, name, friendlyname, status, description, type, parent_id, parent_name, ci_list, obsolescence_flag'
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
            'output_fields' => 'id, name, friendlyname, status, description, type, parent_id, parent_name, ci_list, obsolescence_flag'
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
                'org_id' => "SELECT Organization WHERE name = '$this->organisation'",
                'name' => $name,
                'type' => 'businessDomain',
                'status' => 'production',
                'description' => "area_id=$area_id"
            )
        ));
        $response = $this->DoPostRequest($jsonData);
        if ($response->code != 0) {
            die("Error : ".$response->message);
        }
    }
    public function deleteDomain($id){
        $jsonData = json_encode(array(
            'operation' => 'core/delete',
            'comment' => 'Delete from GRAF',
            'class' => 'Group',
            'key' => $id,
            'simulate' => false
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        if ($response->code != 0) {
            die("Error : ".$response->message);
        }
    }
    public function getBusinessProcesses(){
        $jsonData = json_encode(array(
		    'operation' => 'core/get',
		    'class' => 'BusinessProcess',
		    'key' => 'SELECT BusinessProcess',
		    'output_fields' => 'id, name, friendlyname, description, business_criticity, status, applicationsolutions_list'//, document_name',
		));
        $response = json_decode($this->DoPostRequest($jsonData));
        $result = array();
        foreach ($response->objects as $object){
            $row = new stdClass();
            $row->id = $object->key;
            $row->name = $object->fields->name;
            // TODO trouver comment identifier le group auquel appartient le process
            $row->domain_id = "??";
            $row->domain_name = "???";
            $result[] = $row;
        }
        return $result;
    }
    public function getBusinessProcessById($id){
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class' => 'BusinessProcess',
            'key' => 'SELECT BusinessProcess WHERE id = "'.$id.'"',
            'output_fields' => 'id, name, friendlyname, description, business_criticity, status, applicationsolutions_list'//, document_name',
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        $result = array();
        foreach ($response->objects as $object){
            $row = new stdClass();
            $row->id = $object->key;
            $row->name = $object->fields->name;
            // TODO trouver comment identifier le group auquel appartient le process
            $row->domain_id = "??";
            $row->domain_name = "???";
            $result[] = $row;
        }
        return $result;
    }
    public function getBusinessProcessByDomainId($id){
        // Chercher le domaine
        $clause = 'SELECT Group WHERE type="businessDomain"';
        $clause .= " AND id=".$id;
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class' => 'Group',
            'key' => $clause,
            'output_fields' => 'id, name, ci_list'
        ));
        $processes = "";
        // Récupérer la liste des businessId
        $response = json_decode($this->DoPostRequest($jsonData));
        foreach($response->objects as $object){
            $domainName = $object->fields->name;
            foreach($object->fields->ci_list as $ci){
             if ($ci->ci_id_finalclass_recall == "BusinessProcess"){
                 if (strlen($processes) != 0){
                     $processes .= ",";
                 }
                 $processes .= $ci->ci_id;
             }
            }
        }
        // Chercher les processus
        $result = array();
        if (strlen($processes) != 0){
            $jsonData = json_encode(array(
                'operation' => 'core/get',
                'class' => 'BusinessProcess',
                'key' => 'SELECT BusinessProcess WHERE id IN ('.$processes.')',
                'output_fields' => 'id, name, friendlyname, description, business_criticity, status, applicationsolutions_list' //, document_name',
            ));
            $response = json_decode($this->DoPostRequest($jsonData));
            $result = array();
            foreach ($response->objects as $object){
                $row = new stdClass();
                $row->id = $object->key;
                $row->name = $object->fields->name;
                // TODO trouver comment identifier le domain auquel appartient le process
                $row->domain_id = $id;
                $row->domain_name = $domainName;
                $result[] = $row;
            }
        }
        return $result;
    }
    public function getBusinessProcessStructure($id){
        $xml = $this->getBusinessProcessStructureAsXML($id);
        $xml = new SimpleXMLElement($xml);
        $xml->registerXPathNamespace('prefix', 'http://www.omg.org/spec/BPMN/20100524/MODEL');
        $children = $xml->xpath("//prefix:*");
        $result = array();
        $stepsById = array();
        $links = array();
        foreach($children as $node){
            $name = $node->getName();
            if ($name == "definitions"){
                continue;
            } else if ($name == "startEvent"){
                $step = new stdClass();
                $step->type_name = "START";
            } else if ($name == "endEvent"){
                $step = new stdClass();
                $step->type_name = "END";
            } else if ($name == "userTask"){
                $step = new stdClass();
                $step->type_name = "ACTOR";
            } else if ($name == "exclusiveGateway"){
                $step = new stdClass();
                $step->type_name = "CHOICE";
            } else if ($name == "parallelGateway"){
                $step = new stdClass();
                $step->type_name = "CHOICE";
            } else if ($name == "serviceTask"){
                $step = new stdClass();
                $step->type_name = "SERVICE";
            } else if ($name == "task"){
                $step = new stdClass();
                $step->type_name = "SERVICE";
            } else if ($name == "scriptTask"){
                $step = new stdClass();
                $step->type_name = "SERVICE";
            } else if ($name == "subProcess"){
                $step = new stdClass();
                $step->type_name = "SUB-PROCESS";
            } else if ($name == "sequenceFlow"){
                $link = new stdClass();
                $link->id    = "".$node["id"];
                $link->label = "".$node["name"];
                $link->name  = "".$node["name"];
                $link->from_id  = "".$node["sourceRef"];
                $link->to_id    = "".$node["targetRef"];
                $links[] = $link;
                continue;
            } else {
                error_log("Type non reconnu : $name");
                $step = new stdClass();
                $step->type_name = "SERVICE";
            }
            $step->id    = "".$node["id"];
            $step->name  = "".$node["name"];
            $step->links = array();
            $stepsById[$step->id] = $step;
            $result[] = $step;
        }
        // Raccrocher toutes les étapes entre elles
        foreach ($links as $link){
            $stepFrom = $stepsById[$link->from_id];
            if ($stepFrom == null){
                echo "Step (id=$$link->from_id) not found, skipped";
                continue;
            }
            $stepTo = $stepsById[$link->to_id];
            if ($stepTo == null){
                echo "Step (id=$$link->to_id) not found, skipped";
                continue;
            }
            $link->to 	= $stepTo;
            $stepFrom->links[] = $link;
        }
        return $result;
    }
    public function getBusinessProcessStructureAsXML($id){
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class' => 'BusinessProcess',
            'key' => 'SELECT BusinessProcess WHERE id = "'.$id.'"',
            'output_fields' => 'documents_list'
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        $result = "";
        foreach ($response->objects as $object){
            foreach ($object->fields->documents_list as $document){
                $document_id = $document->document_id;
                $jsonData = json_encode(array(
                    'operation' => 'core/get',
                    'class' => 'DocumentNote',
                    'key' => 'SELECT DocumentNote WHERE id = "'.$document_id.'"',
                    'output_fields' => 'text'
                ));
                $documentResponse = json_decode($this->DoPostRequest($jsonData));
                foreach ($documentResponse->objects as $documentObject){
                    $result = $documentObject->fields->text;
                    break;
                }
                break;
            }
            break;
        }
        // Virer tous les caractères et tags qu'a ajouté ITOP...
        return $this->cleanContent($result);
    }
    public function createBusinessProcess($name,$description,$domain_id){
        error_log("Création d'un BusinessProcess : ".$name);
        // Créer le contenu
        $jsonData = json_encode(array(
            'operation' => 'core/create', // operation code
            'comment' => 'Inserted from GRAF',
            'class' => 'DocumentNote',
            'output_fields' => 'id', // list of fields to show in the results (* or a,b,c)
            // Values for the object to create
            'fields' => array(
                'org_id' => "SELECT Organization WHERE name = '$this->organisation'",
                'name' => "BPMN document of process ".$name,
                'status' => 'published',
                'documenttype_id' => "SELECT DocumentType WHERE name = 'BPMN'",
                'text' => '&lt;hello&gt;&lt;/hello&gt;'
            )
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        $documentid = "";
        foreach($response->objects as $object){
            if ($object->code != 0){
                die($object->message);
            }
            $documentid = $object->fields->id;
        }
        // Créer le BusinessProcess
        $jsonData = json_encode(array(
            'operation' => 'core/create', // operation code
            'comment' => 'Inserted from GRAF',
            'class' => 'BusinessProcess',
            'output_fields' => 'id, friendlyname', // list of fields to show in the results (* or a,b,c)
            // Values for the object to create
            'fields' => array(
                'org_id' => "SELECT Organization WHERE name = '$this->organisation'",
                'name' => $name,
                'status' => 'active',
                'description' => $description,
                'documents_list' => array(array("document_id" => $documentid))
            )
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        if ($response->code != 0) {
            die("Error : ".$response->message);
        }
        // Le rajouter au domaine
        // TODO
    }
    public function deleteBusinessProcess($id){
        // Obtenir le numéro du document
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class' => 'BusinessProcess',
            'key' => 'SELECT BusinessProcess',
            'output_fields' => 'id, documents_list'//, document_name',
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        $result = array();
        $document_id = null;
        foreach ($response->objects as $object){
            foreach ($object->fields->documents_list as $document){
                $document_id = $document->document_id;
                break;
            }
        }
        // Supprimer le document rattaché
        if ($document_id != null){
            // Supprimer le process
            $jsonData = json_encode(array(
                'operation' => 'core/delete',
                'comment' => 'Delete from GRAF',
                'class' => 'DocumentNote',
                'key' => $document_id,
                'simulate' => false
            ));
            $response = json_decode($this->DoPostRequest($jsonData));
        }
        // Supprimer le process
        $jsonData = json_encode(array(
            'operation' => 'core/delete',
            'comment' => 'Delete from GRAF',
            'class' => 'BusinessProcess',
            'key' => $id,
            'simulate' => false
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        if ($response->code != 0) {
            die("Error : ".$response->message);
        }
    }
    public function getViews(){
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class'     => 'DocumentNote',
            'key'       => 'SELECT DocumentNote WHERE documenttype_name = "Template"',
            'output_fields' => 'id, name'
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        $result = array();
        foreach ($response->objects as $object){
            $row = new stdClass();
            $row->id    = $object->key;
            $row->name  = $object->fields->name;
            $result[]   = $row;
        }
        return $result;
    }
    // ********************
    // Chargement des zones
    // ********************
    private function recursive_getViewByName(&$list, $parent, $currentArea){
        $area = new stdClass();
        $area->id        = $currentArea["name"];
        $area->name      = $currentArea["label"];
        $area->code      = $currentArea["name"];
        $area->parent_id = null;
        $area->display   = 'vertical';
        $area->position  = 0;
        $area->elements  = array();
        $area->subareas  = array();
        $area->needed    = false;
        $area->parent    = $parent;
        $list[$area->id] = $area;
        if (isset($currentArea["children"])) {
            foreach($currentArea["children"] as $child){
                $subarea = $this->recursive_getViewByName($list, $area, $child);
                $area->subareas[] = $subarea;
            }
        }
        return $area;
    }
    public function getViewByName($name){
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class'     => 'DocumentNote',
            'key'       => 'SELECT DocumentNote WHERE name = "'.$name.'"',
            'output_fields' => 'text'
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        $text = null;
        foreach ($response->objects as $object){
            $text  = $object->fields->text;
            break;
        }
        $text = $this->cleanContent($text);
        error_log($text);
        $view = json_decode($text, JSON_UNESCAPED_UNICODE);
        $areas = array();
        $this->recursive_getViewByName($areas, null, $view);
        return $areas;
    }
    public function getDB(){
        global $configuration;
        return new mysqli($configuration->db->host, $configuration->db->user, $configuration->db->password, $configuration->db->instance);
    }
    public function disconnect(){
        // Rien à faire
    }
    private function cleanContent($content){
        $result = str_replace(array("<p>","</p>","\n","\r"), "", $content);
        $result = htmlspecialchars_decode(htmlspecialchars_decode($result));
        $result = preg_replace('~\xc2\xa0~', '', $result);
        return $result;
    }
}
$dao = new ITopDao();
?>