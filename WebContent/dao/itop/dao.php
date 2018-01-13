<?php
const DEFAULT_PROCESS_CONTENT = '&lt;bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"&gt;&lt;bpmn:startEvent name="" id="1"/&gt;&lt;/bpmn:definitions&gt;';
const DATA_CLASSES = "~DatabaseSchema~";
const DEVICE_CLASSES = "~NetworkDevice~VirtualDevice~Rack~PhysicalDevice~TelephonyCI~Phone~IPPhone~MobilePhone~ConnectableCI~Printer~DatacenterDevice~TapeLibrary~NAS~SANSwitch~StorageSystem~PC~Enclosure~PowerConnection~PowerSource~PDU~Peripheral~Tablet~VirtualMachine~VirtualHost~Hypervisor~Farm~";
const PROCESS_CLASSES = "~BusinessProcess~";
const SERVER_CLASSES = "~Server~";
const SOFTWARE_CLASSES = "~WebServer~DBServer~WebApplication~MiddlewareInstance~SoftwareInstance~OtherSoftware~PCSoftware~Middleware~";
const SOLUTION_CLASSES = "~ApplicationSolution~";
/**
 * Dao accédant aux données de itop
 */
class ITopDao implements IDAO {
    // déclaration des propriétés
    private $organisation;
    private $url;
    private $login;
    private $password;
    private $version;
    private $ITOP_CATEGORIES;
    private $ITOP_CLASSES;
    public $error = null;
    /**
     * prend en compte les parametres de connection et effectue un test de connection
     * @return boolean true si la connection s'est bien passée, false sinon. Dans ce cas, error contient le message d'erreur
     */
    public function connect(){
        global $configuration;
        $this->organisation = $configuration->itop->organisation;
        $this->url          = $configuration->itop->url;
        $this->login        = $configuration->itop->login;
        $this->password     = $configuration->itop->password;
        $this->version      = $configuration->itop->version;
        try{
            $this->getObjects("Team", "id");
            $result = true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $result = false;
        }
        return $result;
    }
    /**
     * Helper to execute an HTTP POST request
     * Source: http://netevil.org/blog/2006/nov/http-post-from-php-without-curl
     *         originaly named after do_post_request
     * $sOptionnalHeaders is a string containing additional HTTP headers that you would like to send in your request.
     */
    private function DoPostRequest($jsonData, $sOptionnalHeaders = null) {
        $sUrl = $this->url."?version=".$this->version;
        $aData = array();
        $aData['auth_user'] = $this->login;
        $aData['auth_pwd'] = $this->password;
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
            $row->category = $this->_newItemCategory("actor");
            $result[] = $row;
        }
        return $result;
    }
    // déclaration des méthodes
    public function getDomains() {
        $response = $this->getObjects("Group", 'id, name, friendlyname, status, description, type, parent_id, parent_name, ci_list, obsolescence_flag','WHERE type="businessDomain"');
        $result = array();
        foreach ($response->objects as $object){
            $row            = new stdClass();
            $row->id        = $object->key;
            $row->name      = $object->fields->name;
            $row->area_id   = $object->fields->description;
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
        if ($text == null){
            return null;
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
    private function getItemCategoryByClass($className){
        if (!isset($this->ITOP_CLASSES[$className])){
            return $this->_newItemCategory($result);
        }
        $class = $this->ITOP_CLASSES[$className];
        return $class->category;
    }
    public function getItemCategories(){
        return $this->ITOP_CATEGORIES;
    }
    public function getItemClasses(){
        return $this->ITOP_CLASSES;
    }
    public function getItemsByClass($class){
        $result = array();
        $response = $this->getObjects($class,'id, name',"WHERE organization_name = '$this->organisation'");
        foreach ($response->objects as $object){
            $rowclass = $object->class;
            $rowcategory = $this->getItemCategoryByClass($object->class);
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
                $row->category = $this->ITOP_CATEGORIES["actor"];
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
            foreach ($response->objects as $object){
                $rowclass = $object->class;
                if ($category == "*"){
                    $rowcategory = $this->getItemCategoryByClass($object->class);
                } else if (strpos($class,"~".$rowclass."~") === false){
                    continue;
                } else {
                    $rowcategory = $this->_newItemCategory($category);
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
                $row->category = $this->_newItemCategory("actor");
                $result = $row;
                break;
            }
        }
        return $result;
    }
    private function getItemDocumentId($itemId){
        $response = $this->getObjects('FunctionalCI','documents_list',"WHERE id = $itemId");
        $document_id = null;
        foreach ($response->objects as $object){
            // TODO ne pas forcément prendre le premier document venu
            foreach ($object->fields->documents_list as $document){
                $document_id = $document->document_id;
                break;
            }
            break;
        }
        return $document_id;
    }
    public function getItemDocument($itemId,$documentType){
        $document_id = $this->getItemDocumentId($itemId);
        $content = null;
        if ($document_id != null){
            $content = $this->getDocumentContent($document_id);
        }
        if ($content == null){
            return null;
        } else {
            // Virer tous les caractères et tags qu'a ajouté ITOP...
            return $this->cleanContent($content);
        }
    }
    public function updateItemStructure($itemId,$type,$newContent){
        // Vérifier si on a déjà une structure pour cet item
        $document_id = $this->getItemDocumentId($itemId);
        if ($document_id == null){
            $document_id = $this->createDocument("Document for item $itemId", $type, $newContent);
            $businessProcessId = $this->updateObject("FunctionalCI", $itemId, array(
                'documents_list'    => array(array("document_id" => $document_id))
            ));
        } else {
            $this->updateObject("DocumentNote", $document_id, array(
                'text'              => htmlspecialchars($newContent)
            ));
        }
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
    public function getItemsByDomainId($domainId,$class="*"){
        if ($class = "*"){
            $class = "FunctionalCI";
        }
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
        return new mysqli($configuration->db->host, $configuration->db->login, $configuration->db->password, $configuration->db->instance);
    }
    public function disconnect(){
        // Rien à faire
    }
    private function cleanContent($content){
        $result = str_replace(array("<br>","<p>","</p>"), "", $content);
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
    public function createDocument($name,$type,$content){
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
    public function createObject($object,$fields){
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
    public function updateObject($object,$id,$fields){
        $jsonData = json_encode(array(
            'operation' => 'core/update',
            'comment' => 'Update of object',
            'class' => $object,
            'key' => 'SELECT '.$object.' WHERE id='.$id,
            'output_fields' => 'id',
            'fields' => $fields
        ));
        return $this->call($jsonData);
    }
    public function getObjects($object,$fields,$key = ""){
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
    public function _init(){
        $this->ITOP_CATEGORIES = array();
        $this->ITOP_CATEGORIES["actor"]    = $this->_newItemCategory("actor");
        $this->ITOP_CATEGORIES["data"]     = $this->_newItemCategory("data");
        $this->ITOP_CATEGORIES["device"]   = $this->_newItemCategory("device");
        $this->ITOP_CATEGORIES["process"]  = $this->_newItemCategory("process");
        $this->ITOP_CATEGORIES["server"]   = $this->_newItemCategory("server");
        $this->ITOP_CATEGORIES["software"] = $this->_newItemCategory("software");
        $this->ITOP_CATEGORIES["solution"] = $this->_newItemCategory("solution");
        $this->ITOP_CLASSES = array();
        $this->_addItemClass("Team",               $this->ITOP_CATEGORIES["actor"]);
        
        $this->_addItemClass("DatabaseSchema",     $this->ITOP_CATEGORIES["data"]);
        
        $this->_addItemClass("ConnectableCI",      $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("DatacenterDevice",   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Enclosure",          $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Farm",               $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Hypervisor",         $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("IPPhone",            $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("MobilePhone",        $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("NAS",                $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("NetworkDevice",      $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("PC",                 $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("PDU",                $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Peripheral",         $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Phone",              $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("PhysicalDevice",     $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("PowerConnection",    $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("PowerSource",        $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Printer",            $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Rack",               $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("SANSwitch",          $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("StorageSystem",      $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Tablet",             $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("TapeLibrary",        $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("TelephonyCI",        $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("VirtualDevice",      $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("VirtualHost",        $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("VirtualMachine",     $this->ITOP_CATEGORIES["device"]);
        
        $this->_addItemClass("BusinessProcess",    $this->ITOP_CATEGORIES["process"]);
        
        $this->_addItemClass("Server",             $this->ITOP_CATEGORIES["server"]);
        
        $this->_addItemClass("DBServer",           $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("Middleware",         $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("MiddlewareInstance", $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("OtherSoftware",      $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("PCSoftware",         $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("SoftwareInstance",   $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("WebApplication",     $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("WebServer",          $this->ITOP_CATEGORIES["software"]);
        
        $this->_addItemClass("ApplicationSolution",$this->ITOP_CATEGORIES["solution"]);
    }
    private function _addItemClass($className,$category){
        $class = new stdClass();
        $class->id          = $className;
        $class->name        = $className;
        $class->category    = $category;
        $category->classes[] = $class;
        $this->ITOP_CLASSES[$className] = $class;
    }
    private function _newItemCategory($name){
        $category = new stdClass();
        $category->id = $name;
        $category->name = $name;
        $category->classes = array();
        return $category;
    }
}
$dao = new ITopDao();
$dao->_init();
?>