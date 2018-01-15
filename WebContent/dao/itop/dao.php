<?php
const DEFAULT_PROCESS_CONTENT = '&lt;bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"&gt;&lt;bpmn:startEvent name="" id="1"/&gt;&lt;/bpmn:definitions&gt;';
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
    // DEPRECATED Methode à supprimer
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
    
    public function getSubItems($itemId,$category="*"){
        $result = array();
        $itemId = $this->_splitItemId($itemId);
        if ($itemId->prefix == "actor"){ // On recherche les items sous un acteur
            // Chercher tous les items liés à l'équipe
            $response = $this->getObjects("Team","function,cis_list","WHERE id = $itemId->id");
            $domains = "";
            foreach ($response->objects as $object){
                $domains = $object->fields->function;
                foreach($object->fields->cis_list as $item){
                    $rowclass = $item->functionalci_id_finalclass_recall;
                    $rowcategory = $this->getItemCategoryByClass($rowclass);
                    if ($category != "*"){
                        if ($rowcategory->name != $category){
                            continue;
                        }
                    }
                    $row = new stdClass();
                    $row->id    = "item_".$item->functionalci_id;
                    $row->name  = $item->functionalci_name;
                    $row->code  = $item->functionalci_name;
                    $row->domain_id = ""; // TODO voir comment faire
                    $row->domain_name = ""; // TODO voir comment faire
                    $row->class = new stdClass();
                    $row->class->id = 1;
                    $row->class->name = $rowclass;
                    $row->category = $rowcategory;
                    $result[]   = $row;
                }
            }
            // Chercher tous les services
            // Chercher tous les domaines
            $domainList = "";
            foreach(explode(",",$domains) as $domain){
                if (strlen($domainList) > 0){
                    $domainList .= ",";
                }
                $domainList .= "'$domain'";
            }
            $response = $this->getObjects("Group","*","WHERE name IN ($domainList)");
            $rowcategory = $this->getItemCategoryByClass("Group");
            if (($category == "*") || ($rowcategory->name == $category)){
                foreach ($response->objects as $object){
                    $row = new stdClass();
                    $row->id = "domain_".$object->key;
                    $row->name = $object->fields->name;
                    $row->domain_id = ""; // TODO voir comment faire
                    $row->domain_name = ""; // TODO voir comment faire
                    $row->code = $object->fields->name;
                    $row->class = new stdClass();
                    $row->class->id = 1;
                    $row->class->name = "Group";
                    $row->category = $rowcategory;
                    $result[]   = $row;
                }
            }
        } else if ($itemId->prefix == "domain"){ // On recherche les items sous un domaine
            // Chercher le items
            $response = $this->getObjects('Group','id, name, ci_list',"WHERE id=$itemId->id");
            foreach($response->objects as $object){
                $domainName = $object->fields->name;
                foreach($object->fields->ci_list as $ci){
                    $row = new stdClass();
                    $row->id = "item_".$ci->ci_id;
                    $row->name = $ci->ci_name;
                    $row->domain_id = "domain_".$itemId->id;
                    $row->domain_name = $domainName;
                    $row->class = new stdClass();
                    $row->class->id = 1;
                    $row->class->name = $ci->ci_id_finalclass_recall;
                    $row->category = $this->getItemCategoryByClass($row->class->name);
                    if ($category != "*"){
                        if ($row->category->name != $category){
                            continue;
                        }
                    }
                    $result[] = $row;
                }
            }
            // Chercher les services
            if (($category == "*") || ("service" == $category)){
                $key = "%$domainName%";
                $response = $this->getObjects('Service','id, name',"WHERE description LIKE '$key'");
                foreach ($response->objects as $object){
                    $row = new stdClass();
                    $row->id    = "service_".$object->key;
                    $row->name  = $object->fields->name;
                    $row->code  = $object->fields->name;
                    $row->domain_id = "domain_".$itemId->id;
                    $row->domain_name = $domainName;
                    $row->class = new stdClass();
                    $row->class->id = 1;
                    $row->class->name = "Service";
                    $row->category = $this->getItemCategoryByClass($row->class->name);
                    $result[]   = $row;
                }
            }
            // Chercher les acteurs
            if (($category == "*") || ("actor" == $category)){
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
            }
        } else if ($itemId->prefix == "service"){
            // Récupérer la liste des itemsId
            $response = $this->getObjects('Service','id, name, functionalcis_list, contacts_list',"WHERE id = $itemId->id");
            foreach($response->objects as $object){
                //$domainName = $object->fields->description;
                foreach($object->fields->functionalcis_list as $ci){
                    $row = new stdClass();
                    $row->id = "item_".$ci->functionalci_id;
                    $row->name = $ci->functionalci_name;
                    //$row->domain_id = $domainId;
                    //$row->domain_name = $domainName;
                    $row->class = new stdClass();
                    $row->class->id = 1;
                    $row->class->name = $ci->functionalci_id_finalclass_recall;
                    $row->category = $this->getItemCategoryByClass($row->class->name);
                    if ($category != "*"){
                        if ($row->category->name != $category){
                            continue;
                        }
                    }
                    $result[] = $row;
                }
                if (($category == "*") || ("actor" == $category)){
                    foreach($object->fields->contacts_list as $ci){
                        $row = new stdClass();
                        $row->id = "actor_".$ci->contact_id;
                        $row->name = $ci->contact_name;
                        /*$row->domain_id = $domainId;
                         $row->domain_name = $domainName;*/
                        $row->class = new stdClass();
                        $row->class->id = 1;
                        $row->class->name = $ci->contact_id_finalclass_recall;
                        $row->category = $this->getItemCategoryByClass($row->class->name);
                        $result[] = $row;
                    }
                }
            }
        }
        return $result;
    }
    public function createItem($className,$name,$code,$description){
        $result = null;
        if ($className == "Domain"){
            $className = "Group";
        } else if ($className == "Process"){
            $className = "BusinessProcess";
        }
        $category = $this->getItemCategoryByClass($className);
        error_log("Création d'un $category->name de classe $className de nom $name");
        if ($category->name == "domain"){
            $result = $this->createObject('Group', array(
                'org_id'        => "SELECT Organization WHERE name = '$this->organisation'",
                'name'          => $name,
                'type'          => 'businessDomain',
                'status'        => 'production',
                'description'   => $description
            ));
        } else if ($category->name == "service"){
            $result = $this->createObject("Service", array(
                'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
                'name'              => $name,
                'servicefamily_id'  => "SELECT ServiceFamily WHERE name = 'IT Services'",
                'status'            => 'production',
                'description'       => 'description'
            ));
        } else {
            
            // Créer le BusinessProcess
            $result = $this->createObject($className, array(
                'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
                'name'              => $name,
                'status'            => 'active',
                'description'       => $description
            ));
        }
        return $result;
    }
    public function addSubItem($parentItemId,$childItemId){
        $parentItemId = $this->_splitItemId($parentItem);
        $childItemId  = $this->_splitItemId($childItemId);
        $parentItem   = $this->getItemById($parentItemId);
        $childItem    = $this->getItemById($childItemId);
        switch ($parentItem->category->name){
            case "domain" :
                if ($childItem->category->name == "service"){
                    // S'il n'y est pas déjà
                    if (strpos($description,$domain->name) === false){
                        if (strlen($description) == 0){
                            $description = $domain->name;
                        } else {
                            $description = $description.",".$domain->name;
                        }
                        $this->updateObject("Service", $childItemId->id, array(
                            "description" => $description
                        ));
                    } // sinon, c'est bon
                } else if ($childItemId->prefix == "item"){
                    // Le rajouter au domaine
                    $this->createObject("lnkGroupToCI", array(
                        'group_id'  => $parentItemId->id,
                        'ci_id'     => $childItemId->id,
                        'reason'    => 'Item of this domain'
                    ));
                }
                break;
        }
    }
    private function getItemCategoryByClass($className){
        if (!isset($this->ITOP_CLASSES[$className])){
            return $this->_newItemCategory("inconnu");
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
    // TODO refaire cette méthode pour prendre tous les types d'items possibles
    public function getItemsByClass($class){
        $result = array();
        $response = $this->getObjects($class,'id, name',"WHERE organization_name = '$this->organisation'");
        foreach ($response->objects as $object){
            $rowclass = $object->class;
            $row = new stdClass();
            $row->id    = "item_".$object->key; // TODO ajouter item ne va pas marcher dans tous les cas, il faudrait déterminer la catégorie
            $row->name  = $object->fields->name;
            $row->code  = $object->fields->name;
            $row->domain_id = ""; // TODO voir comment faire
            $row->domain_name = ""; // TODO voir comment faire
            $row->class = new stdClass();
            $row->class->id = 1;
            $row->class->name = $object->class;
            $row->category = $this->getItemCategoryByClass($object->class);
            $result[]   = $row;
        }
        return $result;
    }
    public function getItemsByCategory($category, $class="*"){
        $result = array();
        if (($category == "actor") || ($category == "*")){
            $response = $this->getObjects('Team','id, name',"WHERE org_name = '$this->organisation'"); // NB : org_name n'est pas standard, d'habitude c'est organization_name)
            $result = array();
            foreach ($response->objects as $object){
                $row = new stdClass();
                $row->id    = "actor_".$object->key; // en ajoutant actor, cela me permet de savoir que c'est dans Team qu'il faut que j'aille chercher dans getItemById()
                $row->name  = $object->fields->name;
                $row->code  = $object->fields->name;
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = "Team";
                $row->category = $this->ITOP_CATEGORIES["actor"];
                $result[]   = $row;
            }
        }
        if (($category == "domain") || ($category == "*")){
            $response = $this->getObjects("Group", 'id, name, friendlyname, description','WHERE type="businessDomain"');
            $result = array();
            foreach ($response->objects as $object){
                $row            = new stdClass();
                $row->id        = "domain_".$object->key;
                $row->name      = $object->fields->name;
                $row->code      = $object->fields->name;
                $row->area_id   = $object->fields->description;
                $row->class     = new stdClass();
                $row->class->id = 1;
                $row->class->name = "Group";
                $row->category = $this->getItemCategoryByClass($row->class->name);
                $result[] = $row;
            }
        }
        if (($category == "service") || ($category == "*")){
            $response = $this->getObjects('Service','id, name, description',"WHERE organization_name = '$this->organisation'");
            $result = array();
            foreach ($response->objects as $object){
                $row = new stdClass();
                $row->id    = "service_".$object->key;
                $row->name  = $object->fields->name;
                $row->code  = $object->fields->name;
                $row->area_id   = $object->fields->description;
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = $object->class;
                $row->category = $this->getItemCategoryByClass($row->class->name);
                $result[]   = $row;
            }
        }
        if ($category != "actor"){
            $response = $this->getObjects('FunctionalCI','id, name',"WHERE organization_name = '$this->organisation'");
            foreach ($response->objects as $object){
                $rowclass = $object->class;
                $rowcategory = $this->getItemCategoryByClass($rowclass);
                if ($category != "*"){
                    if ($rowcategory->name != $category){
                        continue;
                    }
                }
                $row = new stdClass();
                $row->id    = "item_".$object->key;
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
    public function getItemById($itemId){
        $itemId = $this->_splitItemId($itemId);
        $result = null;
        if ($itemId->prefix == "actor"){ // On recherche un actor
            $response = $this->getObjects('Team','id, name',"WHERE id = '$itemId->id'");
            $result = null;
            foreach ($response->objects as $object){
                $row = new stdClass();
                $row->id    = "actor_".$object->key; // en ajoutant actor_, cela me permet de savoir que c'est dans Team qu'il faut que j'aille chercher dans getItemById()
                $row->name  = $object->fields->name;
                $row->code  = $object->fields->name;
                $row->domain_id = 1; // TODO voir si necessaire
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = "Team";
                $row->category = $this->getItemCategoryByClass($row->class->name);
                $result = $row;
                break;
            }
        } else if ($itemId->prefix == "domain"){ // On recherche un domaine
            $response = $this->getObjects('Group','id, name, description',"WHERE id = '$itemId->id'");
            $result = null;
            foreach ($response->objects as $object){
                $row = new stdClass();
                $row->id    = "domain_".$object->key; // en ajoutant domain_, cela me permet de savoir que c'est dans Group qu'il faut que j'aille chercher dans getItemById()
                $row->name  = $object->fields->name;
                $row->code  = $object->fields->name;
                $row->area_id = $object->fields->description;
                $row->domain_id = 1; // TODO voir si necessaire
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = "Group";
                $row->category = $this->getItemCategoryByClass($row->class->name);
                $result = $row;
                break;
            }
        } else if ($itemId->prefix == "service"){ // On recherche un domaine
            $response = $this->getObjects('Service','id, name',"WHERE id = '$itemId->id'");
            $result = null;
            foreach ($response->objects as $object){
                $row = new stdClass();
                $row->id    = "service_".$object->key; // en ajoutant service_, cela me permet de savoir que c'est dans Group qu'il faut que j'aille chercher dans getItemById()
                $row->name  = $object->fields->name;
                $row->code  = $object->fields->name;
                $row->domain_id = 1; // TODO voir si necessaire
                $row->class = new stdClass();
                $row->class->id = 1;
                $row->class->name = "Service";
                $row->category = $this->getItemCategoryByClass($row->class->name);
                $result = $row;
                break;
            }
        } else if ($itemId->prefix == "item"){ // On recherche un item
            $response = $this->getObjects('FunctionalCI','id, name',"WHERE id = '$itemId->id'");
            $result = null;
            foreach ($response->objects as $object){
                $row = new stdClass();
                $row->id    = "item_".$object->key;
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
        } else {
            // Item inconnu
        }
        return $result;
    }
    public function getItemDocuments($itemId,$documentType="*"){
        $itemId = $this->_splitItemId($itemId);
        $result = array();
        if ($itemId->prefix == "item"){
            $response = $this->getObjects('FunctionalCI','documents_list',"WHERE id = $itemId->id");
            $document_id = null;
            foreach ($response->objects as $object){
                foreach ($object->fields->documents_list as $document){
                    $document_id = $document->document_id;
                    $doc = new stdClass();
                    $doc->id = $document_id;
                    $doc->name = $document->document_name;
                    $subresponse = $this->getObjects("Document","documenttype_name","WHERE id = $doc->id");
                    foreach ($subresponse->objects as $object){
                        $doc->type = $object->fields->documenttype_name;
                        break;
                    }
                    // Filtrer sur le documentType
                    if ($documentType != "*"){
                        if ($doc->type != $documentType){
                            continue;
                        }
                    }
                    $result[] = $doc;
                }
            }
        } else if ($itemId->prefix == "service"){
            $response = $this->getObjects('Service','documents_list',"WHERE id = $itemId->id");
            foreach ($response->objects as $object){
                foreach ($object->fields->documents_list as $document){
                    $document_id = $document->document_id;
                    $doc = new stdClass();
                    $doc->id = $document_id;
                    $doc->name = $document->document_name;
                    $subresponse = $this->getObjects("Document","documenttype_name","WHERE id = $doc->id");
                    foreach ($subresponse->objects as $object){
                        $doc->type = $object->fields->documenttype_name;
                        break;
                    }
                    // Filtrer sur le documentType
                    if ($documentType != "*"){
                        if ($doc->type != $documentType){
                            continue;
                        }
                    }
                    $result[] = $doc;
                }
            }
        } else {
            // rien pour le moment
        }
        return $result;
    }
    public function updateDocument($documentId,$newContent){
        $this->updateObject("DocumentNote", $documentId, array(
             'text'              => htmlspecialchars($newContent)
        ));
    }
    public function deleteItem($itemId){
        // Supprimer les documents liés
        $documents = $this->getItemDocuments($itemId);
        foreach ($documents as $document){
            $this->deleteObject('DocumentNote', $document->id);
        }
        // Supprimer l'item lui même
        $itemId = $this->_splitItemId($itemId);
        $item = $this->getItemById($itemId->id);
        return $this->deleteObject($item->class->name,$item->id);
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
    public function getDocumentContent($id){
        $result = null;
        $documentresponse = $this->getObjects('DocumentNote','text','WHERE id = "'.$id.'"');
        foreach ($documentresponse->objects as $documentObject){
            $result = $documentObject->fields->text;
            break;
        }
        if ($result != null){
            $result = $this->cleanContent($result);
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
    public function addDocument($itemId,$documentId){
        $item = $this->getItemById($itemId);
        $itemId = $this->_splitItemId($itemId);
        // TODO on n'ajoute pas on remplace, il faut gérer l'ajout en redemandant tous les documents
        $businessProcessId = $this->updateObject($item->class->name, $itemId->id, array(
            'documents_list'    => array(array("document_id" => $documentid))
        ));
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
            'operation'     => 'core/update',
            'comment'       => 'Update of object',
            'class'         => $object,
            'key'           => 'SELECT '.$object.' WHERE id='.$id,
            'output_fields' => 'id',
            'fields'        => $fields
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
        $this->ITOP_CATEGORIES["domain"]   = $this->_newItemCategory("domain");
        $this->ITOP_CATEGORIES["process"]  = $this->_newItemCategory("process");
        $this->ITOP_CATEGORIES["server"]   = $this->_newItemCategory("server");
        $this->ITOP_CATEGORIES["service"]  = $this->_newItemCategory("service");
        $this->ITOP_CATEGORIES["software"] = $this->_newItemCategory("software");
        $this->ITOP_CATEGORIES["solution"] = $this->_newItemCategory("solution");
        $this->ITOP_CLASSES = array();
        $this->_addItemClass("Team",               $this->ITOP_CATEGORIES["actor"]);
        
        $this->_addItemClass("DatabaseSchema",     $this->ITOP_CATEGORIES["data"]);
        
        $this->_addItemClass("Group",              $this->ITOP_CATEGORIES["domain"]);
        
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
        
        $this->_addItemClass("BusinessProcess",    $this->ITOP_CATEGORIES["process"]);
        
        $this->_addItemClass("Server",             $this->ITOP_CATEGORIES["server"]);
        $this->_addItemClass("VirtualHost",        $this->ITOP_CATEGORIES["server"]);
        $this->_addItemClass("VirtualMachine",     $this->ITOP_CATEGORIES["server"]);
        
        $this->_addItemClass("Service",            $this->ITOP_CATEGORIES["service"]);
        
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
    private function _splitItemId($itemId){
        $pos = strpos($itemId,"_");
        if ($pos === false){
            throw new Exception("Malformed item id");
        }
        $result = new stdClass();
        $result->id = substr($itemId,$pos+1);
        $result->prefix = substr($itemId,0,$pos);
        return $result;
    }
}
$dao = new ITopDao();
$dao->_init();
?>