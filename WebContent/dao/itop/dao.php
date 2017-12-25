<?php
const DATA_CLASSES = "~DatabaseSchema~";
const DEVICE_CLASSES = "~NetworkDevice~VirtualDevice~Rack~PhysicalDevice~TelephonyCI~Phone~IPPhone~MobilePhone~ConnectableCI~Printer~DatacenterDevice~TapeLibrary~NAS~SANSwitch~StorageSystem~PC~Enclosure~PowerConnection~PowerSource~PDU~Peripheral~Tablet~VirtualMachine~VirtualHost~Hypervisor~Farm~";
const SERVER_CLASSES = "~Server~";
const SOFTWARE_CLASSES = "~WebServer~DBServer~WebApplication~MiddlewareInstance~SoftwareInstance~OtherSoftware~PCSoftware~Middleware~";
const SOLUTION_CLASSES = "~ApplicationSolution~";
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
        $clause .= " AND id=$id";
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
        $result = null;
        if ($response->objects != null){
            foreach ($response->objects as $object){
                $row = new stdClass();
                $row->id = $object->key;
                $row->name = $object->fields->name;
                $row->area_id = $object->fields->description;
                $result = $row;
                break;
            }
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
                'description' => $area_id
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
    private function businessProcessPopulate($object){
        $row = new stdClass();
        $row->id = $object->key;
        $row->name = $object->fields->name;
        // TODO trouver comment identifier le group auquel appartient le process
        $row->domain_id = "??";
        $row->domain_name = "???";
        return $row;
    }
    public function getBusinessProcesses(){
        $jsonData = json_encode(array(
		    'operation' => 'core/get',
		    'class' => 'BusinessProcess',
		    'key' => "SELECT BusinessProcess WHERE organization_name = '$this->organisation'",
		    'output_fields' => 'id, name, friendlyname, description, business_criticity, status, applicationsolutions_list'//, document_name',
		));
        $response = json_decode($this->DoPostRequest($jsonData));
        $result = array();
        foreach ($response->objects as $object){
            $result[] = $this->businessProcessPopulate($object);
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
            $result[] = $this->businessProcessPopulate($object);
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
        $defaultContent = '&lt;bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"&gt;&lt;bpmn:startEvent name="" id="1"/&gt;&lt;/bpmn:definitions&gt;';
        //$defaultContent = urlencode($defaultContent);
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
                'text' => $defaultContent
            )
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        if ($response->code != 0) {
            die("Error : ".$response->message);
        }
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
        $businessProcessId = null;
        foreach ($response->objects as $object){
            if ($object->code != 0){
                die($object->message);
            }
            $businessProcessId = $object->fields->id;
        }
        // Le rajouter au domaine
        $jsonData = json_encode(array(
            'operation' => 'core/create', // operation code
            'comment' => 'Inserted from GRAF',
            'class' => 'lnkGroupToCI',
            'output_fields' => 'id, friendlyname',
            // Values for the object to create
            'fields' => array(
                'group_id' => $domain_id,
                'ci_id' => $businessProcessId,
                'reason' => 'BusinessProcess of this domain'
            )
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        print_r($response);
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
        $view = json_decode($text, JSON_UNESCAPED_UNICODE);
        $areas = parseViewToArray($view);
        return $areas;
    }
    public function getServices(){
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class'     => 'Service',
            'key'       => "SELECT Service WHERE organization_name = '$this->organisation'",
            'output_fields' => 'id, name'
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        $result = array();
        foreach ($response->objects as $object){
            $row = new stdClass();
            $row->id    = $object->key;
            $row->name  = $object->fields->name;
            $row->code  = $object->fields->name;
            $result[]   = $row;
        }
        return $result;
    }
    public function getServiceById($id){
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class'     => 'Service',
            'key'       => "SELECT Service WHERE id = '$id' AND organization_name = '$this->organisation'",
            'output_fields' => 'id, name'
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        $result = null;
        foreach ($response->objects as $object){
            $row = new stdClass();
            $row->id    = $object->key;
            $row->name  = $object->fields->name;
            $row->code  = $object->fields->name;
            $result   = $row;
            break;
        }
        return $result;
    }
    public function getServicesByDomainId($id){
        $domain = $this->getDomainById($id);
        if ($domain == null){
            return array();
        }
        $key = "%$domain->name%";
        $jsonData = json_encode(array(
            'operation' => 'core/get',
            'class'     => 'Service',
            'key'       => "SELECT Service WHERE description LIKE '$key'",
            'output_fields' => 'id, name'
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        $result = array();
        if ($response->objects != null){
            foreach ($response->objects as $object){
                $row = new stdClass();
                $row->id    = $object->key;
                $row->name  = $object->fields->name;
                $row->code  = $object->fields->name;
                $result[]   = $row;
            }
        }
        return $result;
    }
    public function createService($code,$name,$domain_id){
        $domain = $this->getDomainById($domain_id);
        error_log("itop.createService");
        if ($domain == null){
            return false;
        }
        error_log("itop.createService");
        $jsonData = json_encode(array(
            'operation' => 'core/create', // operation code
            'comment' => 'Inserted from GRAF',
            'class' => 'Service',
            'output_fields' => 'id', // list of fields to show in the results (* or a,b,c)
            // Values for the object to create
            'fields' => array(
                'org_id' => "SELECT Organization WHERE name = '$this->organisation'",
                'name' => $name,
                'servicefamily_id' => "SELECT ServiceFamily WHERE name = 'IT Services'",
                'status' => 'production',
                'description' => $domain->name
            )
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        print_r($response);
    }
    public function deleteService($id){
        // Supprimer le process
        $jsonData = json_encode(array(
            'operation' => 'core/delete',
            'comment' => 'Delete from GRAF',
            'class' => 'Service',
            'key' => $id,
            'simulate' => false
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
    }
    private function newItemCategory($name){
        $category = new stdClass();
        $category->id = $name;
        $category->name = $name;
        $category->class = null;
        return $category;
    }
    public function getItemCategories(){
        $result = array();
        // TODO trouver un moyen de ne pas le mettre en dur et surtout définir des classes
        $result[] = $this->newItemCategory("actor");
        $result[] = $this->newItemCategory("data");
        $result[] = $this->newItemCategory("device");
        $result[] = $this->newItemCategory("server");
        $result[] = $this->newItemCategory("software");
        $result[] = $this->newItemCategory("solution");
        return $result;
    }
    public function getItemsByCategory($category){
        $result = array();
        if (($category == "actor") || ($category == "*")){
            $jsonData = json_encode(array(
                'operation' => 'core/get',
                'class'     => 'Team',
                'key'       => "SELECT Team WHERE org_name = '$this->organisation'", // NB : org_name n'est pas standard, d'habitude c'est organization_name
                'output_fields' => 'id, name'
            ));
            $response = json_decode($this->DoPostRequest($jsonData));
            $result = array();
            if ($response->objects != null){
                foreach ($response->objects as $object){
                    $row = new stdClass();
                    $row->id    = "actor_".$object->key; // en ajoutant actor, cela me permet de savoir que c'est dans Team qu'il faut que j'aille chercher dans getItemById()
                    $row->name  = $object->fields->name;
                    $row->code  = $object->fields->name;
                    $row->domain_id = 1; // TODO voir si necessaire
                    $row->class = new stdClass();
                    $row->class->id = 1;
                    $row->class->name = "Team";
                    $row->category = $this->newItemCategory("actor");
                    $result[]   = $row;
                }
            }
        }
        if ($category != "actor"){
            $class = "";
            // Les ~ permettent d'éviter que "Server" soit considéré comme software parce qu'il y a "DBServer" dans la liste
            if ($category == "data") {
                $class = DATA_CLASSES;
            } else if ($category == "device") {
                $class = DEVICE_CLASSES;
            } else if ($category == "server") {
                $class = SERVER_CLASSES;
            } else if ($category == "software") {
                $class = SOFTWARE_CLASSES;
            } else if ($category == "solution") {
                $class = SOLUTION_CLASSES;
            } else {
                $class = "UNDEFINED";
            }
            $jsonData = json_encode(array(
                'operation' => 'core/get',
                'class'     => 'FunctionalCI',
                'key'       => "SELECT FunctionalCI WHERE organization_name = '$this->organisation'",
                'output_fields' => 'id, name'
            ));
            $response = json_decode($this->DoPostRequest($jsonData));
            $result = array();
            if ($response->objects != null){
                foreach ($response->objects as $object){
                    $rowclass = $object->class;
                    $rowcategory = $category;
                    if ($category == "*"){
                        if ($rowclass == "BusinessProcess"){
                            continue;
                        }
                        if (strpos(DATA_CLASSES,"~".$rowclass."~") !== false){
                            $rowcategory = "data";
                        } else if (strpos(DEVICE_CLASSES,"~".$rowclass."~") !== false){
                            $rowcategory = "device";
                        } else if (strpos(SERVER_CLASSES,"~".$rowclass."~") !== false){
                            $rowcategory = "server";
                        } else if (strpos(SOFTWARE_CLASSES,"~".$rowclass."~") !== false){
                            $rowcategory = "software";
                        } else if (strpos(SOLUTION_CLASSES,"~".$rowclass."~") !== false){
                            $rowcategory = "solution";
                        } else {
                            $rowcategory = "????";
                        }
                    } else if (strpos($class,"~".$rowclass."~") === false){
                        continue;
                    }
                    $row = new stdClass();
                    $row->id    = $object->key;
                    $row->name  = $object->fields->name;
                    $row->code  = $object->fields->name;
                    $row->domain_id = 1; // TODO voir si necessaire
                    $row->class = new stdClass();
                    $row->class->id = 1;
                    $row->class->name = $object->class;
                    $row->category = $this->newItemCategory($rowcategory);
                    $result[]   = $row;
                }
            }
        }
        return $result;
    }
    public function getItems(){
        return $this->getItemsByCategory("*");
    }
    public function getItemById($id){
        $result = array();
        if (strpos("actor",$id) == 0){ // On recherche un actor
            $id = substr($id,6);
            $jsonData = json_encode(array(
                'operation' => 'core/get',
                'class'     => 'Team',
                'key'       => "SELECT Team WHERE id = '$id'",
                'output_fields' => 'id, name'
            ));
            $response = json_decode($this->DoPostRequest($jsonData));
            $result = null;
            if ($response->objects != null){
                foreach ($response->objects as $object){
                    $row = new stdClass();
                    $row->id    = "actor_".$object->key; // en ajoutant actor, cela me permet de savoir que c'est dans Team qu'il faut que j'aille chercher dans getItemById()
                    $row->name  = $object->fields->name;
                    $row->code  = $object->fields->name;
                    $row->domain_id = 1; // TODO voir si necessaire
                    $row->class = new stdClass();
                    $row->class->id = 1;
                    $row->class->name = "Team";
                    $row->category = $this->newItemCategory("actor");
                    $result = $row;
                    break;
                }
            }
        } else {
            $jsonData = json_encode(array(
                'operation' => 'core/get',
                'class'     => 'FunctionalCI',
                'key'       => "SELECT FunctionalCI WHERE id = '$id'",
                'output_fields' => 'id, name'
            ));
            $response = json_decode($this->DoPostRequest($jsonData));
            $result = null;
            if ($response->objects != null){
                foreach ($response->objects as $object){
                    $rowclass = $object->class;
                    if (strpos($class,"~".$rowclass."~") === false){
                        error_log("Filtering ".$object->fields->name." ".$rowclass." not in ".$class);
                        continue;
                    }
                    $row = new stdClass();
                    $row->id    = $object->key;
                    $row->name  = $object->fields->name;
                    $row->code  = $object->fields->name;
                    $row->domain_id = 1; // TODO voir si necessaire
                    $row->class = new stdClass();
                    $row->class->id = 1;
                    $row->class->name = $object->class;
                    $row->category = $this->newItemCategory($category);
                    $result   = $row;
                    break;
                }
            }
        }
        return $result;
    }
    public function getItemsByDomain($domainId){
	return array();
    }
    public function getSubItems($itemID){
	$jsonData = json_encode(array(
                'operation' => 'core/get',
                'class'     => 'FunctionalCI',
                'key'       => "SELECT FunctionalCI WHERE id = '$itemID'",
                'output_fields' => 'id, name'
        ));
        $response = json_decode($this->DoPostRequest($jsonData));
        $result = array();
        if ($response->objects != null){
           foreach ($response->objects as $object){
	      foreach ($object->fields->functionalcis_list as $subitem) {
                 $rowclass = $object->class;
                 if (strpos($class,"~".$rowclass."~") === false){
                    error_log("Filtering ".$object->fields->name." ".$rowclass." not in ".$class);
                    continue;
                 }
                 $row = new stdClass();
                 $row->id    = $object->key;
                 $row->name  = $object->fields->name;
                 $row->code  = $object->fields->name;
                 $row->domain_id = 1; // TODO voir si necessaire
                 $row->class = new stdClass();
                 $row->class->id = 1;
                 $row->class->name = $object->class;
                 $row->category = $this->newItemCategory($category);
                 $result[]   = $row;
	      }
	      break;
           }
        }
	return $result;
    }
    public function getDB(){
        global $configuration;
        return new mysqli($configuration->db->host, $configuration->db->user, $configuration->db->password, $configuration->db->instance);
    }
    public function disconnect(){
        // Rien à faire
    }
    private function cleanContent($content){
        $result = str_replace(array("<br>","<p>","</p>","\n","\r"), "", $content);
        $result = htmlspecialchars_decode(htmlspecialchars_decode($result));
        $result = preg_replace('~\xc2\xa0~', '', $result);
        return $result;
    }
}
$dao = new ITopDao();
?>
