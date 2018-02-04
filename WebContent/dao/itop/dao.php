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
        try {
            $this->getObjects("Team", "id");
            $result = true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $result = false;
        }
        return $result;
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
    //
    // Gestion des items
    //
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
            $result = "domain_".$this->createObject('Group', array(
                'org_id'        => "SELECT Organization WHERE name = '$this->organisation'",
                'name'          => $name,
                'type'          => 'BusinessDomain',
                'status'        => 'production',
                'description'   => $description
            ));
        } else if ($className == "Data"){
            $result = "domain_".$this->createObject('Group', array(
                'org_id'        => "SELECT Organization WHERE name = '$this->organisation'",
                'name'          => $name,
                'type'          => 'DataModel',
                'status'        => 'production',
                'description'   => $description
            ));
        } else if ($category->name == "service"){
            $result = "service_".$this->createObject("Service", array(
                'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
                'name'              => $name,
                'servicefamily_id'  => "SELECT ServiceFamily WHERE name = 'IT Services'",
                'status'            => 'production',
                'description'       => $description
            ));
        } else if ($category->name == "actor"){
            $result = "service_".$this->createObject("Team", array(
                'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
                'name'              => $name,
                'function'          => $description
            ));
        } else {
            $fields = array(
                'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
                'name'              => $name,
                'description'       => $description
            );
            if (($className == 'DBServer') || ($className == 'Middleware') || ($className == 'OtherSoftware') || ($className == 'PCSoftware') || ($className == 'WebServer')){
                $fields['system_id'] = $this->getGenericServerId();
            } else if ($className == 'DatabaseSchema') {
                $fields['dbserver_id'] = $this->getGenericDBServerId();
            }
            // Créer un item
            $result = "item_".$this->createObject($className, $fields);
        }
        return $result;
    }
    public function addSubItem($aParentItemId,$aChildItemId){
        error_log("addSubItem($aParentItemId,$aChildItemId)");
        $parentItem   = $this->getItemById($aParentItemId);
        $childItem    = $this->getItemById($aChildItemId);
        $parentItemId = $this->_splitItemId($aParentItemId);
        $childItemId  = $this->_splitItemId($aChildItemId);
        switch ($parentItem->category->name){
            case 'domain' : // ajouter un élément dans un domaine
                if ($childItem->category->name == 'service'){
                    // S'il n'y est pas déjà
                    $description = $childItem->description;
                    if (strpos($description,$parentItem->name) === false){
                        if (strlen($description) == 0){
                            $description = $parentItem->name;
                        } else {
                            $description = $description.','.$parentItem->name;
                        }
                        $this->updateObject('Service', $childItemId->id, array(
                            'description' => $description
                        ));
                    } // sinon, c'est bon
                } else if ($childItemId->prefix == 'actor'){
                    $response = $this->getObjects('Team', 'function','WHERE id = '.$childItemId->id);
                    foreach ($response->objects as $object){
                        $function = $object->fields->function;
                        if (strpos($function,$parentItem->name) === false){
                            if (strlen($function) == 0){
                                $function = $parentItem->name;
                            } else {
                                $function = $function.",".$parentItem->name;
                            }
                            $this->updateObject('Team', $childItemId->id, array(
                                'function' => $function
                            ));
                            break;
                        }
                    }
                } else if ($childItemId->prefix == "data"){
                    // Creation d'un DatabaseSchema
                    $id = $this->createItem("DatabaseSchema", $childItem->name, "", $childItemId->id);
                    $id = $this->_splitItemId($id)->id;
                    // Le rajouter au DataModel
                    $this->createObject("lnkGroupToCI", array(
                        'group_id'      => $childItemId->id,
                        'ci_id'         => $id,
                        'reason'        => 'Data instance'
                    ));
                    // Le rajouter au Domaine
                    $this->createObject("lnkGroupToCI", array(
                        'group_id'      => $parentItemId->id,
                        'ci_id'         => $id,
                        'reason'        => 'Data of this domain'
                    ));
                } else if ($this->isFunctionalCI($childItemId->prefix)){
                    // Le rajouter au domaine
                    $this->createObject("lnkGroupToCI", array(
                        'group_id'  => $parentItemId->id,
                        'ci_id'     => $childItemId->id,
                        'reason'    => 'Item of this domain'
                    ));
                } else {
                    // TODO supporter l'ajout de 'software'
                    throw new Exception("Unable to add ".$childItem->category->name." in ".$parentItem->category->name);
                }
                break;
            case 'solution' : // ajouter dans une solution
                if ($childItem->category->name == "software"){ // ajouter un logiciel
                    $response = $this->getObjects("Software", "type","WHERE id = ".$childItemId->id);
                    foreach ($response->objects as $object){
                        $type = $object->fields->type;
                        break;
                    }
                    if ($type == ''){
                        $type = 'OtherSoftware';
                    }
                    $id = $this->createObject($type, array(
                        'org_id'        => "SELECT Organization WHERE name = '$this->organisation'",
                        'name'          => $childItem->name,
                        'system_id'     => $this->getGenericServerId(),
                        'software_id'   => $childItemId->id,
                        'description'   => 'SoftwareInstance of '.$childItem->name.' for solution '.$parentItem->name
                    ));
                    $this->createObject("lnkApplicationSolutionToFunctionalCI", array(
                        'applicationsolution_id'  => $parentItemId->id,
                        'functionalci_id'         => $id
                    ));
                } else if ($childItem->category->name == "data"){
                    // Creation d'un DatabaseSchema
                    $id = $this->createItem("DatabaseSchema", $childItem->name, "", $childItemId->id);
                    $id = $this->_splitItemId($id)->id;
                    // Le rajouter au DataModel
                    $this->createObject("lnkGroupToCI", array(
                        'group_id'      => $childItemId->id,
                        'ci_id'         => $id,
                        'reason'        => 'Data instance'
                    ));
                    // Le rajouter à la solution
                    $this->createObject("lnkApplicationSolutionToFunctionalCI", array(
                        'applicationsolution_id'  => $parentItemId->id,
                        'functionalci_id'         => $id
                    ));
                } else {
                    throw new Exception("Unable to add ".$childItem->category->name." in ".$parentItem->category->name);
                }
                break;
            case "service" : // ajouter un élément dans un service
                if ($this->isFunctionalCI($childItem->category->name)) { // ajouter un FunctionalCI
                    $response = $this->getObjects('lnkFunctionalCIToService', 'id', 'WHERE service_id = '.$parentItemId->id.' AND functionalci_id = '.$childItemId->id);
                    if (count($response->objects) == 0){
                        $this->createObject('lnkFunctionalCIToService', array(
                            'service_id'        => $parentItemId->id,
                            'functionalci_id'   => $childItemId->id
                        ));
                    }
                } else if ($childItem->category->name == "actor"){
                    $response = $this->getObjects('lnkContactToService', 'id', 'WHERE service_id = '.$parentItemId->id.' AND contact_id = '.$childItemId->id);
                    if (count($response->objects) == 0){
                        $this->createObject('lnkContactToService', array(
                            'service_id'    => $parentItemId->id,
                            'contact_id'    => $childItemId->id
                        ));
                    }
                } else if ($childItem->category->name == "software"){
                    $response = $this->getObjects("Software", "type","WHERE id = ".$childItemId->id);
                    foreach ($response->objects as $object){
                        $type = $object->fields->type;
                        break;
                    }
                    if ($type == ''){
                        $type = 'OtherSoftware';
                    }
                    $id = $this->createObject($type, array(
                        'org_id'        => "SELECT Organization WHERE name = '$this->organisation'",
                        'name'          => $childItem->name,
                        'system_id'     => $this->getGenericServerId(),
                        'software_id'   => $childItemId->id,
                        'description'   => 'SoftwareInstance of '.$childItem->name.' for solution '.$parentItem->name
                    ));
                    $this->createObject("lnkFunctionalCIToService", array(
                        'service_id'        => $parentItemId->id,
                        'functionalci_id'   => $id
                    ));
                } else if ($childItem->category->name == "data"){
                    // Creation d'un DatabaseSchema
                    $id = $this->createItem("DatabaseSchema", $childItem->name, "", $childItemId->id);
                    $id = $this->_splitItemId($id)->id;
                    // Le rajouter au DataModel
                    $this->createObject("lnkGroupToCI", array(
                        'group_id'      => $childItemId->id,
                        'ci_id'         => $id,
                        'reason'        => 'Data instance'
                    ));
                    // Le rajouter à la solution
                    $this->createObject("lnkFunctionalCIToService", array(
                        'service_id'        => $parentItemId->id,
                        'functionalci_id'   => $id
                    ));
                } else {
                    throw new Exception("Unable to add ".$childItem->category->name." in ".$parentItem->category->name);
                }
                break;
            case "actor" : // ajouter un élément à un actor
                if ($this->isFunctionalCI($childItemId->prefix)){
                    $response = $this->getObjects("lnkContactToFunctionalCI", 'id', 'WHERE contact_id = '.$parentItemId->id.' AND functionalci_id = '.$childItemId->id);
                    if (count($response->objects) == 0){
                        $this->createObject("lnkContactToFunctionalCI", array('contact_id' => $parentItemId->id, 'functionalci_id' => $childItemId->id));
                    }
                } else if ($childItem->category->name == "domain"){
                    $this->addSubItem($aChildItemId,$aParentItemId); // on inverse
                } else if ($childItem->category->name == "service"){
                    $this->addSubItem($aChildItemId,$aParentItemId); // on inverse
                } else {
                    // TODO supporter l'ajout de software
                    throw new Exception("Unable to add ".$childItem->category->name." from ".$parentItem->category->name);
                }
                break;
            default:
                throw new Exception("Unable to add ".$childItem->category->name." in ".$parentItem->category->name);
                break;
        }
    }
    public function removeSubItem($aParentItemId,$aChildItemId){
        error_log("removeSubItem($aParentItemId,$aChildItemId)");
        $parentItem   = $this->getItemById($aParentItemId);
        $childItem    = $this->getItemById($aChildItemId);
        $parentItemId = $this->_splitItemId($aParentItemId);
        $childItemId  = $this->_splitItemId($aChildItemId);
        switch ($parentItem->category->name){
            case "domain" : // retirer un élément d'un domaine
                if ($childItemId->prefix == "actor"){
                    $response = $this->getObjects("Team", "function","WHERE id = ".$childItemId->id);
                    foreach ($response->objects as $object){
                        $function = $object->fields->function;
                        $pos = strpos($function,$parentItem->name);
                        if ($pos === false){
                            // rien à faire
                        } else {
                            $function = substr($function, 0, $pos).substr($function,$pos+strlen($parentItem->name)+1);
                            $function = trim($function, ','); // virer les éventuelles virgules qui restent après suppression
                            $this->updateObject("Team", $childItemId->id, array(
                                "function" => $function
                            ));
                            break;
                        }
                    }
                } else if ($this->isFunctionalCI($childItemId->prefix)){
                    $response = $this->deleteObject("lnkGroupToCI", array('group_id' => $parentItemId->id, 'ci_id' => $childItemId->id));
                } else if ($childItemId->prefix == "data") {
                    $this->deleteObject('DatabaseSchema', $childItemId->id);
                } else if ($childItemId->prefix == "service") {
                    // S'il n'y est pas déjà
                    $description = $childItem->description;
                    $pos = strpos($description,$parentItem->name);
                    if ($pos === false){
                        // c'est bon
                    } else {
                        $description = substr($description, 0, $pos).substr($description,$pos+strlen($parentItem->name)+1);
                        $description = trim($description, ','); // virer les éventuelles virgules qui restent après suppression
                        $this->updateObject("Service", $childItemId->id, array(
                            "description" => $description
                        ));
                    }
                } else {
                    throw new Exception("Unable to remove ".$childItem->category->name." from ".$parentItem->category->name);
                }
                break;
            case "service" : // retirer un élément d'un service
                if ($childItemId->prefix == "actor"){
                    $this->deleteObject('lnkContactToService', array('service_id' => $parentItemId->id, 'contact_id' => $childItemId->id));
                } else if ($this->isFunctionalCI($childItemId->prefix) || ($childItemId->prefix == "software")) {
                    $this->deleteObject('lnkFunctionalCIToService', array('service_id' => $parentItemId->id, 'functionalci_id' => $childItemId->id));
                } else if ($childItemId->prefix == "data") {
                    $this->deleteObject('DatabaseSchema', $childItemId->id);
                } else {
                    throw new Exception("Unable to remove ".$childItem->category->name." from ".$parentItem->category->name);
                }
                break;
            case "actor" : // retirer un élément d'un actor
                if ($this->isFunctionalCI($childItemId->prefix)){
                    $response = $this->deleteObject("lnkContactToFunctionalCI", array('contact_id' => $parentItemId->id, 'functionalci_id' => $childItemId->id));
                } else if ($childItemId->prefix == "domain"){
                    $this->removeSubItem($aChildItemId,$aParentItemId);// on inverse
                } else if ($childItem->category->name == "service"){
                    $this->removeSubItem($aChildItemId,$aParentItemId);// on inverse
                } else {
                    throw new Exception("Unable to remove ".$childItem->category->name." from ".$parentItem->category->name);
                }
                break;
            default:
                throw new Exception("Unable to remove ".$childItem->category->name." from ".$parentItem->category->name);
                break;
        }
    }
    public function getRelatedItems($aItemId,$category='*',$direction='down'){
        if ($direction == 'down'){
            return $this->getSubItems($aItemId,$category);
        } else if ($direction == 'up'){
            return $this->getOverItems($aItemId,$category);
        } else {
            throw new Exception('Unsupported '.$direction);
        }
    }
    private function getOverItems($aItemId,$category='*'){
        $result = array();
        $itemId = $this->_splitItemId($aItemId);
        if ($itemId->prefix == 'actor'){ // On recherche les domaines auquel appartient un acteur
            /*$response = $this->getObjects('Team', 'function', 'WHERE id = '.$itemId->id);
            foreach ($response->objects as $object){
                $function = $object->fields->function;
                ICI
                break;
            }*/
        } else if ($this->isFunctionalCI($itemId->prefix)){
            // Trouver les groupes liés
            if (($category == '*') || ($category == 'domain')){
                // Le rajouter au domaine
                $response = $this->getObjects("lnkGroupToCI", '*', 'WHERE ci_id = '.$itemId->id);
                foreach ($response->objects as $object){
                    $result[] = $this->_newItem($object->fields->group_id, $object->fields->group_name, "Group");
                }
            }
            if (($category == '*') || ($category == 'solution') || ($category == 'process') || ($category == 'device') || ($category == 'software')){
                // Trouver tous les items 
                $item = $this->getItemById($aItemId);
                $response = $this->getRelated($item->class->name,$itemId->id,'down');
                foreach ($response->objects as $object){
                    $result[] = $this->_newItem($object->fields->id, $object->fields->friendlyname, $object->class);
                }
            }
        } else {
        }
        return $result;
    }
    private function getSubItems($aItemId,$category='*'){
        $result = array();
        $itemId = $this->_splitItemId($aItemId);
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
                    $result[]   = $this->_newItem($item->functionalci_id, $item->functionalci_name, $rowclass);
                }
            }
            // Chercher tous les services
            $response = $this->getObjects('lnkContactToService', 'service_id, service_name, contact_id_finalclass_recall', 'WHERE contact_id = '.$itemId->id);
            foreach ($response->objects as $object){
                $result[]   = $this->_newItem($object->fields->service_id, $object->fields->service_name, 'Service');
            }
            // Chercher tous les domaines
            $domainList = "";
            foreach(explode(",",$domains) as $domain){
                if (strlen($domainList) > 0){
                    $domainList .= ",";
                }
                $domainList .= "'$domain'";
            }
            $response = $this->getObjects("Group","*","WHERE name IN ($domainList)");
            if (($category == "*") || ($rowcategory->name == $category)){
                foreach ($response->objects as $object){
                    $result[]   = $this->_newItem($object->key, $object->fields->name, "Group");
                }
            }
        } else if ($itemId->prefix == "domain"){ // On recherche les items sous un domaine
            // Chercher le items
            $response = $this->getObjects('Group','id, name, ci_list',"WHERE id=$itemId->id");
            foreach($response->objects as $object){
                $domainName = $object->fields->name;
                foreach($object->fields->ci_list as $ci){
                    $item = $this->_newItem($ci->ci_id, $ci->ci_name, $ci->ci_id_finalclass_recall);
                    if ($category != "*"){
                        if ($item->category->name != $category){
                            continue;
                        }
                    }
                    $result[] = $item;
                }
            }
            // Chercher les services
            if (($category == "*") || ("service" == $category)){
                $key = "%$domainName%";
                $response = $this->getObjects('Service','id, name',"WHERE description LIKE '$key'");
                foreach ($response->objects as $object){
                    $result[]   = $this->_newItem($object->fields->id, $object->fields->name, "Service");
                }
            }
            // Chercher les acteurs
            if (($category == "*") || ("actor" == $category)){
                $response = $this->getObjects("Team","id, name","WHERE function LIKE '%$domainName%'");
                foreach ($response->objects as $object){
                    $result[] = $this->_newItem($object->fields->id, $object->fields->name, "Team");
                }
            }
        } else if ($itemId->prefix == "service"){
            // Récupérer la liste des itemsId
            $response = $this->getObjects('Service','id, name, functionalcis_list, contacts_list',"WHERE id = $itemId->id");
            foreach($response->objects as $object){
                //$domainName = $object->fields->description;
                foreach($object->fields->functionalcis_list as $ci){
                    $item = $this->_newItem($ci->functionalci_id, $ci->functionalci_name, $ci->functionalci_id_finalclass_recall);
                    if ($category != "*"){
                        if ($item->category->name != $category){
                            continue;
                        }
                    }
                    $result[] = $item;
                }
                if (($category == "*") || ("actor" == $category)){
                    foreach($object->fields->contacts_list as $ci){
                        $result[] = $this->_newItem($ci->contact_id, $ci->contact_name, $ci->contact_id_finalclass_recall);
                    }
                }
            }
        } else {
            $item = $this->getItemById($aItemId);
            if ($item->class->name == 'Data'){
                $item->class->name = 'Group';
            }
            $response = $this->getRelated($item->class->name,$itemId->id,'up');
            foreach ($response->objects as $object){
                $result[] = $this->_newItem($object->fields->id, $object->fields->friendlyname, $object->class);
            }
        }
        return $result;
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
    public function getItemsByClass($className){
        $category = $this->getItemCategoryByClass($className);
        if ($category->name == "actor"){
            return $this->getItemsByCategory("actor");
        } else if ($category->name == "domain"){
            return $this->getItemsByCategory("domain");
        } else if ($category->name == "data"){
            return $this->getItemsByCategory("data");
        } else if ($category->name == "service"){
            return $this->getItemsByCategory("service");
        } else if ($className == "Software"){
            $result = array();
            $response = $this->getObjects($className,'id, name, version');
            foreach ($response->objects as $object){
                $result[]   = $this->_newItem($object->key, $object->fields->name." ".$object->fields->version, $object->class);
            }
        } else {
            // pour le reste, la solution générique devrait fonctionner        
            $result = array();
            $response = $this->getObjects($className,'id, name',"WHERE organization_name = '$this->organisation'");
            foreach ($response->objects as $object){
                $result[]   = $this->_newItem($object->key, $object->fields->name, $object->class);
            }
        }
        return $result;
    }
    public function getItemsByCategory($category, $class="*"){
        $result = array();
        if (($category == "actor") || ($category == "*")){
            $response = $this->getObjects('Team','id, name',"WHERE org_name = '$this->organisation'"); // NB : org_name n'est pas standard, d'habitude c'est organization_name)
            foreach ($response->objects as $object){
                $result[]   = $this->_newItem($object->fields->id, $object->fields->name, "Team");
            }
        }
        if (($category == "domain") || ($category == "*")){
            $response = $this->getObjects("Group", 'id, name, friendlyname, description','WHERE type="BusinessDomain"');
            foreach ($response->objects as $object){
                $item = $this->_newItem($object->fields->id, $object->fields->name, "Group");
                $item->area_id   = $object->fields->description;
                $result[] = $item;
            }
        }
        if (($category == "data") || ($category == "*")){
            $response = $this->getObjects("Group", 'id, name, friendlyname, description','WHERE type="DataModel"');
            foreach ($response->objects as $object){
                $item = $this->_newItem($object->fields->id, $object->fields->name, "Data");
                $item->area_id   = $object->fields->description;
                $result[] = $item;
            }
        }
        if (($category == "service") || ($category == "*")){
            $response = $this->getObjects('Service','id, name, description',"WHERE organization_name = '$this->organisation'");
            foreach ($response->objects as $object){
                $item = $this->_newItem($object->fields->id, $object->fields->name, $object->class);
                $item->area_id   = $object->fields->description;
                $result[]   = $item;
            }
        }
        if (($category == "software") || ($category == "*")){
            $items = $this->getItemsByClass("Software");
            foreach($items as $item){
                $result[]   = $item;
            }
        }
        if (($category != "actor") && ($category != "data") && ($category != "domain") && ($category != "service") && ($category != "software")){
            $response = $this->getObjects('FunctionalCI','id, name, description',"WHERE organization_name = '$this->organisation' AND finalclass NOT IN ('DBServer','Middleware','OtherSoftware','PCSoftware','WebServer','WebApplication')");
            foreach ($response->objects as $object){
                $item = $this->_newItem($object->fields->id, $object->fields->name, $object->class, $object->fields->description);
                if ($item->class->name == "DatabaseSchema"){
                    continue;
                }
                if ($category != "*"){
                    if ($item->category->name != $category){
                        continue;
                    }
                }
                $result[]   = $item;
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
            foreach ($response->objects as $object){
                $result = $this->_newItem($object->fields->id, $object->fields->name, "Team");
                break;
            }
        } else if (($itemId->prefix == "domain") || ($itemId->prefix == "data")){ // On recherche un domaine ou une donnée
            $response = $this->getObjects('Group','id, name, type, description',"WHERE id = '$itemId->id'");
            if (count($response->objects) != 0){
                foreach ($response->objects as $object){
                    if ($itemId->prefix == "domain"){
                        $result = $this->_newItem($object->fields->id, $object->fields->name, "Group");
                    } else if ($itemId->prefix == "data"){
                        $result = $this->_newItem($object->fields->id, $object->fields->name, "Data");
                    } else {
                        continue;
                    }
                    $result->area_id       = $object->fields->description;
                    break;
                }
            } else if ($itemId->prefix == "data") {
                $response = $this->getObjects('FunctionalCI','id, name',"WHERE id = '$itemId->id'");
                foreach ($response->objects as $object){
                    $result = $this->_newItem($object->fields->id, $object->fields->name, $object->class);
                    break;
                }
            }
        } else if ($itemId->prefix == "service"){ // On recherche un domaine
            $response = $this->getObjects('Service','id, name, description',"WHERE id = '$itemId->id'");
            foreach ($response->objects as $object){
                $result = $this->_newItem($object->fields->id, $object->fields->name, "Service");
                $result->area_id = $object->fields->description;
                $result->description = $object->fields->description;
                break;
            }
        } else if ($itemId->prefix == "software"){ // On recherche un Software
            $response = $this->getObjects('Software','id, name',"WHERE id = '$itemId->id'");
            if (count($response->objects) != 0){
                foreach ($response->objects as $object){
                    $result = $this->_newItem($object->fields->id, $object->fields->name, $object->class);
                    break;
                }
            } else {
                $response = $this->getObjects('FunctionalCI','id, name',"WHERE id = '$itemId->id'");
                foreach ($response->objects as $object){
                    $result = $this->_newItem($object->fields->id, $object->fields->name, $object->class);
                    break;
                }
            }
        } else { // tout autre item
            $response = $this->getObjects('FunctionalCI','id, name, description',"WHERE id = '$itemId->id'");
            foreach ($response->objects as $object){
                $result = $this->_newItem($object->fields->id, $object->fields->name, $object->class, $object->fields->description);
                break;
            }
        }
        return $result;
    }
    public function addItemDocument($itemId,$documentId){
        $item = $this->getItemById($itemId);
        $itemId = $this->_splitItemId($itemId);
        // TODO on n'ajoute pas on remplace, il faut gérer l'ajout en redemandant tous les documents
        $this->updateObject($item->class->name, $itemId->id, array(
            'documents_list'    => array(array('document_id' => $documentId))
        ));
    }
    private function isFunctionalCI($prefix){
        if (($prefix == 'solution') || ($prefix == 'device') || ($prefix == 'process') || ($prefix == 'server')){
            return true;
        } else {
            return false;
        }
    }
    public function getItemDocuments($itemId,$documentType='*'){
        $itemId = $this->_splitItemId($itemId);
        $result = array();
        if ($this->isFunctionalCI($itemId->prefix)){
            $response = $this->getObjects('FunctionalCI','documents_list',"WHERE id = $itemId->id");
            $document_id = null;
            foreach ($response->objects as $object){
                foreach ($object->fields->documents_list as $document){
                    $document_id = $document->document_id;
                    $doc = new stdClass();
                    $doc->id = $document_id;
                    $doc->name = $document->document_name;
                    $subresponse = $this->getObjects('Document','documenttype_name',"WHERE id = $doc->id");
                    foreach ($subresponse->objects as $subobject){
                        $doc->type = $subobject->fields->documenttype_name;
                        break;
                    }
                    // Filtrer sur le documentType
                    if ($documentType != '*'){
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
    public function deleteItem($itemId){
        // Supprimer les documents liés
        $documents = $this->getItemDocuments($itemId);
        foreach ($documents as $document){
            $this->deleteObject('DocumentNote', $document->id);
        }
        // Supprimer l'item lui même
        $item = $this->getItemById($itemId);
        if ($item->class->name == "Data"){
            // Supprimer toutes les SchemaInstance du Group Data
            $items = $this->getRelatedItems($itemId,"*","down");
            foreach ($items as $subitem) {
                $this->deleteItem($subitem->id);
            }
            $item->class->name = 'Group';
        }
        $itemId = $this->_splitItemId($itemId);
        return $this->deleteObject($item->class->name,$itemId->id);
    }
    private function getGenericServerId(){
        $GenericServerName = 'GenericServer';
        $response = $this->getObjects('FunctionalCI', 'id', "WHERE finalclass IN ('Server','VirtualMachine','PC') AND name = '$GenericServerName'");
        if (count($response->objects) == 0){
            $id = $this->createObject('Server', array(
                'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
                'name'              => $GenericServerName,
                'description'       => 'Generic Server'
            ));
            return $id;
        } else {
            foreach ($response->objects as $object){
                return $object->fields->id;
            }
        }
        return null;
    }
    private function getGenericDBServerId(){
        $GenericDBServerName = 'GenericDBServer';
        $response = $this->getObjects('FunctionalCI', 'id', "WHERE finalclass = 'DBServer' AND name = '$GenericDBServerName'");
        if (count($response->objects) == 0){
            $id = $this->createObject('DBServer', array(
                'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
                'name'              => $GenericDBServerName,
                'system_id'         => $this->getGenericServerId(),
                'description'       => 'Generic DBServer'
            ));
            return $id;
        } else {
            foreach ($response->objects as $object){
                return $object->fields->id;
            }
        }
        return null;
    }
    //@deprecated : à supprimer prochainement
    public function getDB(){
        global $configuration;
        return new mysqli($configuration->db->host, $configuration->db->login, $configuration->db->password, $configuration->db->instance);
    }
    public function disconnect(){
        // Rien à faire
    }
    //
    // Partie liée aux documents
    //
    /**
     * 
     * @param unknown $content
     * @return unknown
     */
    private function cleanContent($content){
        $result = str_replace(array("<br>","<p>","</p>"), "", $content);
        $result = htmlspecialchars_decode(htmlspecialchars_decode($result));
        $result = preg_replace('~\xc2\xa0~', '', $result);
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
    public function updateDocument($documentId,$newContent){
        $this->updateObject("DocumentNote", $documentId, array(
            'text'              => htmlspecialchars($newContent)
        ));
    }
    //
    // Méthodes permettant de simplifier les appels à l'API rest
    //    
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
    public function getRelated($object,$id,$direction = "down"){
        $clause = "SELECT ".$object." WHERE id = ".$id;
        $jsonData = json_encode(array(
            'operation'     => 'core/get_related',
            'class'         => $object,
            'key'           => $clause,
            'output_fields' => '*',
            'relation'      => 'impacts',
            'depth'         => 1,
            'direction'     => $direction
        ));
        $result = $this->call($jsonData);
        // Retirer le premier qui est l'item demandé
        $newObjects = array();
        $first = true;
        foreach ($result->objects as $object){
            if (!$first){
                $newObjects[] = $object;
            }
            $first = false;
        }
        $result->objects = $newObjects;
        return $result;
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
    //
    // Méthodes internes gérant les categories et classes
    //
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
        $this->_addItemClass("Team",               false,   $this->ITOP_CATEGORIES["actor"]);
        
        $this->_addItemClass("DatabaseSchema",     true,    $this->ITOP_CATEGORIES["data"]);    // Probleme 'dbserver_id'
        $this->_addItemClass("Data",               false,   $this->ITOP_CATEGORIES["data"]);
        
        $this->_addItemClass("Group",              false,   $this->ITOP_CATEGORIES["domain"]);
        
        $this->_addItemClass("ConnectableCI",      true,    $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("DatacenterDevice",   true,    $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Enclosure",          true,    $this->ITOP_CATEGORIES["device"]); // Problem 'rack_id'
        $this->_addItemClass("IPPhone",            false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("MobilePhone",        false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("NAS",                false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("NetworkDevice",      true,    $this->ITOP_CATEGORIES["device"]); // Problem 'networkdevicetype_id'
        $this->_addItemClass("PC",                 false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("PDU",                true,    $this->ITOP_CATEGORIES["device"]);  // Problem 'rack_id'
        $this->_addItemClass("Peripheral",         false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Phone",              false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("PhysicalDevice",     true,    $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("PowerConnection",    true,    $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("PowerSource",        false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Printer",            false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Rack",               false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("SANSwitch",          false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("StorageSystem",      false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("Tablet",             false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("TapeLibrary",        false,   $this->ITOP_CATEGORIES["device"]);
        $this->_addItemClass("TelephonyCI",        true,    $this->ITOP_CATEGORIES["device"]);
        
        $this->_addItemClass("BusinessProcess",    false,   $this->ITOP_CATEGORIES["process"]);
        
        $this->_addItemClass("Server",             false,   $this->ITOP_CATEGORIES["server"]);
        $this->_addItemClass("VirtualHost",        true,    $this->ITOP_CATEGORIES["server"]);
        $this->_addItemClass("VirtualMachine",     true,    $this->ITOP_CATEGORIES["server"]); // Problem 'virtualhost_id'
        $this->_addItemClass("Hypervisor",         false,   $this->ITOP_CATEGORIES["server"]);
        $this->_addItemClass("Farm",               false,   $this->ITOP_CATEGORIES["server"]);
        $this->_addItemClass("VirtualDevice",      true,    $this->ITOP_CATEGORIES["server"]);
        
        $this->_addItemClass("Service",            false,   $this->ITOP_CATEGORIES["service"]);
        
        
        $this->_addItemClass("Software",           false,   $this->ITOP_CATEGORIES["software"]);
        
        $this->_addItemClass("DBServer",           true,   $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("Middleware",         true,   $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("MiddlewareInstance", true,   $this->ITOP_CATEGORIES["software"]);// Problem 'middleware_id'
        $this->_addItemClass("OtherSoftware",      true,   $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("PCSoftware",         true,   $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("SoftwareInstance",   true,   $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("WebApplication",     true,   $this->ITOP_CATEGORIES["software"]);// Problem 'webserver_id'
        $this->_addItemClass("WebServer",          true,   $this->ITOP_CATEGORIES["software"]);
        
        $this->_addItemClass("ApplicationSolution",false,   $this->ITOP_CATEGORIES["solution"]);
    }
    private function _addItemClass($className,$abstract,$category){
        $class = new stdClass();
        $class->id          = $className;
        $class->name        = $className;
        $class->category    = $category;
        $class->abstract    = $abstract;
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
    private function _newItem($id,$name,$className,$description = ''){
        $result = new stdClass();
        /*if ($className == 'DatabaseSchema') {
            $className = 'Data';
            if (strlen($description) > 0){
                $id = $description;
            } else {
                $response = $this->getObjects('FunctionalCI','description','WHERE id = '.$id);
                foreach ($response->objects as $object){
                    $id = $object->fields->description;
                    break;
                }
            }
        }/* else if (($className == 'DBServer') || ($className == 'Middleware') || ($className == 'OtherSoftware') || ($className == 'PCSoftware') || ($className == 'WebServer')) {
            $className = 'Software';
            $response = $this->getObjects('FunctionalCI','software_id','WHERE id = '.$id);
            foreach ($response->objects as $object){
                $id = $object->fields->description;
                break;
            }
        }*/
        $category = $this->getItemCategoryByClass($className);
        $result->id    = $category->name."_".$id;
        $result->name  = $name;
        $result->class = new stdClass();
        $result->class->id = 1;
        $result->class->name = $className;
        $result->category = $category;
        return $result;
    }
    private function _splitItemId($itemId){
  //      error_log("_splitItemId($itemId)");
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