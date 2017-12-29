<?php
const DATA_CLASSES = "~DatabaseSchema~";
const DEVICE_CLASSES = "~NetworkDevice~VirtualDevice~Rack~PhysicalDevice~TelephonyCI~Phone~IPPhone~MobilePhone~ConnectableCI~Printer~DatacenterDevice~TapeLibrary~NAS~SANSwitch~StorageSystem~PC~Enclosure~PowerConnection~PowerSource~PDU~Peripheral~Tablet~VirtualMachine~VirtualHost~Hypervisor~Farm~";
const PROCESS_CLASSES = "~BusinessProcess~";
const SERVER_CLASSES = "~Server~";
const SOFTWARE_CLASSES = "~WebServer~DBServer~WebApplication~MiddlewareInstance~SoftwareInstance~OtherSoftware~PCSoftware~Middleware~";
const SOLUTION_CLASSES = "~ApplicationSolution~";
const DEFAULT_SOLUTION_CONTENT = '&lt;bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"&gt;&lt;/bpmn:definitions&gt;';
const DEFAULT_PROCESS_CONTENT = '&lt;bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"&gt;&lt;bpmn:startEvent name="" id="1"/&gt;&lt;/bpmn:definitions&gt;';
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
                if (isset($php_errormsg)) {
                    throw new Exception("Problem with $sUrl, $php_errormsg");
                } else {
                    throw new Exception("Problem with $sUrl");
                }
         }
         $response = @stream_get_contents($fp);
         if ($response === false) {
             throw new Exception("Problem reading data from $sUrl, $php_errormsg");
         }
         return $response;
    }
    private function call($jsonData){
        $response = $this->DoPostRequest($jsonData);
        $response = json_decode($response);
        if ($response->code != 0) {
            throw new Exception("Problem calling ITOP Method ".$response->message);
        }
        if (!isset($response->objects)){
            $response->objects = array();
        }
        return $response;
    }
    public function getActorsByDomain($domainName) {
        $result = array();
        $response = $this->getObjects("Team","id, name","WHERE function = '$domainName'");
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
            $result[] = $row;
        }
        return $result;
    }
    // déclaration des méthodes
    public function getDomains() {
        $response = $this->getObjects("Group", 'id, name, friendlyname, status, description, type, parent_id, parent_name, ci_list, obsolescence_flag','WHERE type="businessDomain"');
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
        $clause = 'WHERE type="businessDomain"';
        $clause .= " AND id=$id";
        $response = $this->getObjects('Group','id, name, friendlyname, status, description, type, parent_id, parent_name, ci_list, obsolescence_flag',$clause);
        $result = null;
        foreach ($response->objects as $object){
            $row = new stdClass();
            $row->id = $object->key;
            $row->name = $object->fields->name;
            $row->area_id = $object->fields->description;
            $result = $row;
            break;
        }
        return $result;
    }
    public function createDomain($name,$area_id){
        error_log("Création d'un domaine : ".$name);
        $id = $this->createObject('Group', array(
            'org_id'        => "SELECT Organization WHERE name = '$this->organisation'",
            'name'          => $name,
            'type'          => 'businessDomain',
            'status'        => 'production',
            'description'   => $area_id
        ));
        return $id;
    }
    public function deleteDomain($id){
        return $this->deleteObject('Group',$id);
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
        $response = $this->getObjects('BusinessProcess', 'id, name, friendlyname, description, business_criticity, status, applicationsolutions_list',"WHERE organization_name = '$this->organisation'");
        $result = array();
        foreach ($response->objects as $object){
            $result[] = $this->businessProcessPopulate($object);
        }
        return $result;
    }
    public function getBusinessProcessById($id){
        $response = $this->getObjects('BusinessProcess','id, name, friendlyname, description, business_criticity, status, applicationsolutions_list','WHERE id = "'.$id.'"');
        $result = array();
        foreach ($response->objects as $object){
            $result[] = $this->businessProcessPopulate($object);
        }
        return $result;
    }
    public function getBusinessProcessByDomainId($id){
        // Chercher le domaine
        $processes = "";
        // Récupérer la liste des businessId
        $response = $this->getObjects('Group','id, name, ci_list',"WHERE id=$id");
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
            $response = $this->getObjects('BusinessProcess','id, name, friendlyname, description, business_criticity, status, applicationsolutions_list','WHERE id IN ('.$processes.')');
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
        $response = $this->getObjects('BusinessProcess','documents_list',"WHERE id = $id");
        $content = null;
        foreach ($response->objects as $object){
            foreach ($object->fields->documents_list as $document){
                $document_id = $document->document_id;
                $content = $this->getDocumentContent($document_id);
                break;
            }
            break;
        }
        // Virer tous les caractères et tags qu'a ajouté ITOP...
        return $this->cleanContent($content);
    }
    public function createBusinessProcess($name,$description,$domain_id){
        error_log("Création d'un BusinessProcess : ".$name);
        // Créer le contenu
        $documentid = $this->createDocument("BPMN document of process ".$name, "BPMN", DEFAULT_PROCESS_CONTENT);
        // Créer le BusinessProcess
        $businessProcessId = $this->createObject("BusinessProcess", array(
            'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
            'name'              => $name,
            'status'            => 'active',
            'description'       => $description,
            'documents_list'    => array(array("document_id" => $documentid))
        ));
        // Le rajouter au domaine
        $this->createObject("lnkGroupToCI", array(
            'group_id'  => $domain_id,
            'ci_id'     => $businessProcessId,
            'reason'    => 'BusinessProcess of this domain'
        ));
    }
    public function deleteBusinessProcess($id){
        // Obtenir le numéro du document
        $response = $this->getObjects('BusinessProcess','id, documents_list',"WHERE id = $id");
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
            // Supprimer le document
            $this->deleteObject('DocumentNote',$document_id);
        }
        // Supprimer le process
        return $this->deleteObject('BusinessProcess',$id);
    }
    public function getViews(){
        $response = $this->getObjects('DocumentNote','id, name','WHERE documenttype_name = "Template"');
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
        $response = $this->getObjects('DocumentNote','text','WHERE name = "'.$name.'"');
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
        $response = $this->getObjects('Service','id, name',"WHERE organization_name = '$this->organisation'");
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
        $response = $this->getObjects('Service','id, name',"WHERE id = '$id' AND organization_name = '$this->organisation'");
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
        $response = $this->getObjects('Service','id, name',"WHERE description LIKE '$key'");
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
    public function createService($code,$name,$domain_id){
        $domain = $this->getDomainById($domain_id);
        if ($domain == null){
            return null;
        }
        // Créer le service
        $id = $this->createObject("Service", array(
            'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
            'name'              => $name,
            'servicefamily_id'  => "SELECT ServiceFamily WHERE name = 'IT Services'",
            'status'            => 'production',
            'description'       => $domain->name
        ));
        return $id;
    }
    public function deleteService($id){
        // Supprimer le service
        return $this->deleteObject('Service',$id);
    }
    private function newItemCategory($name){
        $category = new stdClass();
        $category->id = $name;
        $category->name = $name;
        $category->class = null;
        return $category;
    }
    private function getItemCategoryByClass($class){
        $result = null;
        if ($class == "Team") {
            $result = "actor";
        } else if ($class == "Service") {
            $result = "service";
        } else if ($class == "BusinessProcess"){
            $result = "process";
        } else if (strpos(DATA_CLASSES,"~".$class."~") !== false){
            $result = "data";
        } else if (strpos(DEVICE_CLASSES,"~".$class."~") !== false){
            $result = "device";
        } else if (strpos(SERVER_CLASSES,"~".$class."~") !== false){
            $result = "server";
        } else if (strpos(SOFTWARE_CLASSES,"~".$class."~") !== false){
            $result = "software";
        } else if (strpos(SOLUTION_CLASSES,"~".$class."~") !== false){
            $result = "solution";
        } else if (strpos(PROCESS_CLASSES,"~".$class."~") !== false){
            $result = "process";
        } else {
            $result = "Unable to determine category from ".$class;
        }
        return $this->newItemCategory($result);
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
            $response = $this->getObjects('Team','id, name',"WHERE org_name = '$this->organisation'"); // NB : org_name n'est pas standard, d'habitude c'est organization_name)
            $result = array();
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
        if ($category != "actor"){
            $class = "";
            // Les ~ permettent d'éviter que "Server" soit considéré comme software parce qu'il y a "DBServer" dans la liste
            if ($category == "data") {
                $class = DATA_CLASSES;
            } else if ($category == "device") {
                $class = DEVICE_CLASSES;
            } else if ($category == "process") {
                $class = PROCESS_CLASSES;
            } else if ($category == "server") {
                $class = SERVER_CLASSES;
            } else if ($category == "software") {
                $class = SOFTWARE_CLASSES;
            } else if ($category == "solution") {
                $class = SOLUTION_CLASSES;
            } else {
                $class = "UNDEFINED";
            }
            $response = $this->getObjects('FunctionalCI','id, name',"WHERE organization_name = '$this->organisation'");
            $result = array();
            foreach ($response->objects as $object){
                $rowclass = $object->class;
                if ($category == "*"){
                    $rowcategory = $this->getItemCategoryByClass($object->class);
                } else if (strpos($class,"~".$rowclass."~") === false){
                    continue;
                } else {
                    $rowcategory = $this->newItemCategory($category);
                }
                $row = new stdClass();
                $row->id    = $object->key;
                $row->name  = $object->fields->name;
                $row->code  = $object->fields->name;
                $row->domain_id = 1; // TODO voir si necessaire
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = $object->class;
                $row->category = $rowcategory;
                $result[]   = $row;
            }
        }
        return $result;
    }
    public function getItems(){
        return $this->getItemsByCategory("*");
    }
    public function getItemById($id){
        $result = null;
        if (strpos($id,"actor") === false){ // On ne recherche pas un actor
            $response = $this->getObjects('FunctionalCI','id, name',"WHERE id = '$id'");
            $result = null;
            foreach ($response->objects as $object){
                $row = new stdClass();
                $row->id    = $object->key;
                $row->name  = $object->fields->name;
                $row->code  = $object->fields->name;
                $row->domain_id = 1; // TODO voir si necessaire
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = $object->class;
                $row->category = $this->getItemCategoryByClass($object->class);
                $result   = $row;
                break;
            }
        } else { // On recherche un actor
            $id = substr($id,6);
            $response = $this->getObjects('Team','id, name',"WHERE id = '$id'");
            $result = null;
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
        return $result;
    }
    public function getItemsByServiceId($serviceId){
        $result = array();
        // Récupérer la liste des itemsId
        $response = $this->getObjects('Service','id, name, functionalcis_list, contacts_list',"WHERE id = $serviceId");
        foreach($response->objects as $object){
            $domainName = $object->fields->name;
            foreach($object->fields->functionalcis_list as $ci){
                $row = new stdClass();
                $row->id = $ci->functionalci_id;
                $row->name = $ci->functionalci_name;
                /*$row->domain_id = $domainId;
                $row->domain_name = $domainName;*/
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = $ci->functionalci_id_finalclass_recall;
                $row->category = $this->getItemCategoryByClass($ci->functionalci_id_finalclass_recall);
                $result[] = $row;
            }
            foreach($object->fields->contacts_list as $ci){
                $row = new stdClass();
                $row->id = "actor_".$ci->contact_id;
                $row->name = $ci->contact_name;
                /*$row->domain_id = $domainId;
                 $row->domain_name = $domainName;*/
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = $ci->contact_id_finalclass_recall;
                $row->category = $this->getItemCategoryByClass($ci->contact_id_finalclass_recall);
                $result[] = $row;
            }
        }
        return $result;
    }
    public function getItemsByDomainId($domainId,$class="FunctionalCI"){
        // Chercher le items
        $result = array();
        $response = $this->getObjects('Group','id, name, ci_list',"WHERE id=$domainId");
        foreach($response->objects as $object){
            $domainName = $object->fields->name;
            foreach($object->fields->ci_list as $ci){
                $row = new stdClass();
                $row->id = $ci->ci_id;
                $row->name = $ci->ci_name;
                $row->domain_id = $domainId;
                $row->domain_name = $domainName;
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = $ci->ci_id_finalclass_recall;
                $row->category = $this->getItemCategoryByClass($ci->ci_id_finalclass_recall);
                $result[] = $row;
            }
        }
        $domain = $this->getDomainById($domainId);
        // Chercher les acteurs
        $actors = $this->getActorsByDomain($domain->name);
        foreach ($actors as $actor){
            $result[] = $actor;
        }
        return $result;
    }
    public function createSolution($name){
        // Créer le contenu
        $documentid = $this->createDocument("BPMN document of service ".$name, "BPMN", DEFAULT_SOLUTION_CONTENT);
        // Créer la solution
        $this->createObject('ApplicationSolution', array(
            'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
            'name'              => $name,
            'documents_list'    => array(array("document_id" => $documentid))
        ));
    }
    public function deleteSolution($id){
        // Supprimer le contenu
        $response = $this->getObjects('Service','documents_list',"WHERE id = $id");
        foreach ($response->objects as $object){
            foreach ($object->fields->documents_list as $document){
                $this->deleteObject('DocumentNote', $document->document_id);
            }
        }
        // Supprimer la solution
        $this->deleteObject('ApplicationSolution', $id);
    }
    public function getSolutionStructure($itemID){
        $result = array();
        $xml = $this->getSolutionStructureAsXML($itemID);
        if ($xml == null){
            $xml = DEFAULT_SOLUTION_CONTENT;
        }
        $xml = new SimpleXMLElement($xml);
        $xml->registerXPathNamespace('prefix', 'http://www.omg.org/spec/BPMN/20100524/MODEL');
        $result = array();
        $stepsById = array();
        $links = array();
        $children = $xml->xpath("//prefix:*");
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
    public function getSolutionStructureAsXML($itemID){
        $response = $this->getObjects('ApplicationSolution','documents_list',"WHERE id = $itemID");
        $content = null;
        foreach ($response->objects as $object){
            foreach ($object->fields->documents_list as $document){
                $document_id = $document->document_id;
                $content = $this->getDocumentContent($document_id);
                break;
            }
            break;
        }
        if ($content == null){
            return $content;
        } else {
            // Virer tous les caractères et tags qu'a ajouté ITOP...
            return $this->cleanContent($content);
        }
    }
    public function getSolutionItems($itemID){
	   $response = $this->getObjects('ApplicationSolution','id, name, functionalcis_list',"WHERE id = $itemID");
       $result = array();
       foreach ($response->objects as $object){
           foreach ($object->fields->functionalcis_list as $subitem) {
                $row = new stdClass();
                $row->id    = $subitem->functionalci_id;
                $row->name  = $subitem->functionalci_name;
                $row->code  = $subitem->functionalci_name;
                $row->domain_id = 1; // TODO voir si necessaire
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = $subitem->functionalci_id_finalclass_recall;
                $row->category = $this->getItemCategoryByClass($subitem->functionalci_id_finalclass_recall);
                $result[]   = $row;
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
    private function getDocumentContent($id){
        $result = null;
        $documentresponse = $this->getObjects('DocumentNote','text','WHERE id = "'.$id.'"');
        foreach ($documentresponse->objects as $documentObject){
            $result = $documentObject->fields->text;
            break;
        }
        return $result;
    }
    private function createDocument($name,$type,$content){
        // Créer le contenu
        $documentid = $this->createObject('DocumentNote', array(
            'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
            'name'              => $name,
            'status'            => 'published',
            'documenttype_id'   => "SELECT DocumentType WHERE name = '$type'",
            'text'              => $content
        ));
        return $documentid;
    }
    private function createObject($object,$fields){
        $jsonData = json_encode(array(
            'operation'     => 'core/create', // operation code
            'comment'       => 'Inserted from GRAF',
            'class'         => $object,
            'output_fields' => 'id',
            'fields'        => $fields
        ));
        // Créer et récupérer l'id
        $response = $this->call($jsonData);
        $id = "";
        foreach($response->objects as $object){
            if ($object->code != 0){
                die($object->message);
            }
            $id = $object->fields->id;
            break;
        }
        return $id;
    }
    private function getObjects($object,$fields,$key = ""){
        $clause = "SELECT ".$object;
        if ($key != ""){
            $clause .= " ".$key;
        }
        $jsonData = json_encode(array(
            'operation'     => 'core/get',
            'class'         => $object,
            'key'           => $clause,
            'output_fields' => $fields
        ));
        return $this->call($jsonData);
    }
    private function deleteObject($object,$id){
        $jsonData = json_encode(array(
            'operation' => 'core/delete',
            'comment'   => 'Delete from GRAF',
            'class'     => $object,
            'key'       => $id,
            'simulate'  => false
        ));
        return $this->call($jsonData);
    }
}
$dao = new ITopDao();
?>
