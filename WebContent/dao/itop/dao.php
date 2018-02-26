<?php
//const DEFAULT_PROCESS_CONTENT = '&lt;bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"&gt;&lt;bpmn:startEvent name="" id="1"/&gt;&lt;/bpmn:definitions&gt;';
const DATA_INSTANCE_REASON = 'Data instance';
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
    public function createItem($className,$name,$code,$description,$properties){
        $result = null;
        if ($className == "Domain"){
            $className = "Group";
        } else if ($className == "Process"){
            $className = "BusinessProcess";
        }
        $category = $this->getItemCategoryByClass($className);
        error_log("Création d'un $category->name de classe $className de nom $name");
        if ($category->name == "domain"){
        	$result = $category->name."_".$this->createObject('Group', array(
                'org_id'        => "SELECT Organization WHERE name = '$this->organisation'",
                'name'          => $name,
                'type'          => 'BusinessDomain',
                'status'        => 'production',
                'description'   => $properties['area_id']
            ));
        } else if ($className == "Data"){
        	$result = $category->name."_".$this->createObject('Group', array(
                'org_id'        => "SELECT Organization WHERE name = '$this->organisation'",
                'name'          => $name,
                'type'          => 'DataModel',
                'status'        => 'production',
                'description'   => $description
            ));
        } else if ($category->name == "service"){
        	$result = $category->name."_".$this->createObject("Service", array(
                'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
                'name'              => $name,
                'servicefamily_id'  => "SELECT ServiceFamily WHERE name = 'IT Services'",
                'status'            => 'production',
                'description'       => $description
            ));
        } else if ($category->name == "actor"){
        	$result = $category->name."_".$this->createObject("Team", array(
                'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
                'name'              => $name,
                'function'          => $description
            ));
        } else if ($className == "Software"){
        	$result = $category->name."_".$this->createObject('Software', array(
        			'name'              => $name,
        			'type'              => $properties['type'],
        			'version'			=> $properties['version'],
        			'vendor'			=> $properties['vendor']
        	));
        } else if ($className == "Location"){
        	$fields = array(
        			'org_id'            => "SELECT Organization WHERE name = '$this->organisation'",
        			'name'              => $name,
        			'city'       		=> $properties['city'],
        			'country'      		=> $properties['country']
        	);
        	// Créer un item
        	$result = $category->name."_".$this->createObject($className, $fields);
        /*} else if ($category->name == "software"){
            $result = "item_".$this->createObject('Software', array(
                'name'              => $name,
                'type'              => $className,
            	'version'			=> $properties['version'],
            	'vendor'			=> $properties['vendor']
            ));*/
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
            if ($className == 'VirtualMachine'){
            	$fields['virtualhost_id'] = $properties['virtualhost_id'];
            }
            // Créer un item
            $result = $category->name."_".$this->createObject($className, $fields);
        }
        return $result;
    }
    public function addSubItem($aParentItemId,$aChildItemId){
        error_log("addSubItem($aParentItemId,$aChildItemId)");
        $parentItem   = $this->getItems((object)['id'=>$aParentItemId])[0];
        $childItem    = $this->getItems((object)['id'=>$aChildItemId])[0];
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
                        'reason'        => DATA_INSTANCE_REASON
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
            case 'process' : // ajouter dans un processus
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
                } else if ($this->isFunctionalCI($childItem->category->name)){
                    $response = $this->getObjects('lnkApplicationSolutionToFunctionalCI', 'id', 'WHERE applicationsolution_id = '.$parentItemId->id.' AND functionalci_id = '.$childItemId->id);
                    if (count($response->objects) == 0){
                        $this->createObject("lnkApplicationSolutionToFunctionalCI", array(
                            'applicationsolution_id' => $parentItemId->id,
                            'functionalci_id'        => $childItemId->id
                        ));
                    }
                } else if ($childItem->category->name == "actor"){
                	$this->addSubItem($aChildItemId,$aParentItemId); // on inverse
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
            case "data" : // ajouter un élément à un data
            	if ($childItem->category->name == "domain"){
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
        $parentItem   = $this->getItems((object)['id'=>$aParentItemId])[0];
        $childItem    = $this->getItems((object)['id'=>$aChildItemId])[0];
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
                    $this->deleteObject("lnkGroupToCI", array('group_id' => $parentItemId->id, 'ci_id' => $childItemId->id));
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
                    $this->deleteObject("lnkContactToFunctionalCI", array('contact_id' => $parentItemId->id, 'functionalci_id' => $childItemId->id));
                } else if ($childItemId->prefix == "domain"){
                    $this->removeSubItem($aChildItemId,$aParentItemId);// on inverse
                } else if ($childItem->category->name == "service"){
                    $this->removeSubItem($aChildItemId,$aParentItemId);// on inverse
                } else {
                    throw new Exception("Unable to remove ".$childItem->category->name." from ".$parentItem->category->name);
                }
                break;
            case "solution" : // retirer un élément d'une solution
                if ($this->isFunctionalCI($childItemId->prefix) || ($childItemId->prefix == 'software')){
                    $this->deleteObject("lnkApplicationSolutionToFunctionalCI", array('applicationsolution_id' => $parentItemId->id, 'functionalci_id' => $childItemId->id));
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
    	error_log("getOverItems $aItemId");
        $result = array();
        $itemId = $this->_splitItemId($aItemId);
        if ($itemId->prefix == 'actor'){ // On recherche les domaines auquel appartient un acteur
            // Chercher tous les domaines
        	$response = $this->getObjects('Team','function',"WHERE id = $itemId->id");
        	$domains = '';
        	foreach ($response->objects as $object){
        		$domains = $object->fields->function;
        		break;
        	}
            $domainList = '';
            foreach(explode(',',$domains) as $domain){
                if (strlen($domainList) > 0){
                    $domainList .= ',';
                }
                $domainList .= "'$domain'";
            }
            $response = $this->getObjects("Group","*","WHERE name IN ($domainList)");
            if (($category == "*") || ($rowcategory->name == $category)){
            	foreach ($response->objects as $object){
            		$result[]   = $this->_newItem($object->key, $object->fields->name, "Group");
            	}
            }
        } else if ($itemId->prefix == 'location'){
        	if (strpos($itemId->id,'country_') !== false){ // si c'est un pays, on ramène toutes les villes au dessus
        		$pos = strpos($itemId->id,'_');
        		$realId = substr($itemId->id,$pos+1);
        		// D'abord on recherche le nom du pays
        		$response = $this->getObjects('Location','id, country','WHERE id = '.$realId);
        		foreach ($response->objects as $object){
        			$countryName = $object->fields->country;
        			break;
        		}
        		// On recherche les villes
        		$dejaCite = array();
        		$response = $this->getObjects('Location','id, city, country','WHERE country = "'.$countryName.'"');
        		foreach ($response->objects as $object){
        			if (isset($dejaCite[$object->fields->city])){
        				continue;
        			}
        			$dejaCite[$object->fields->city] = true;
        			$result[] = $this->_newItem('city_'.$object->fields->id, $object->fields->city, "Location",['city' => $object->fields->city,'country' => $object->fields->country]);
        		}
        	} else if (strpos($itemId->id,'city_') !== false){ // Si c'est une ville, on prend toutes les locations de la ville
        		$pos = strpos($itemId->id,'_');
        		$realId = substr($itemId->id,$pos+1);
        		// D'abord on recherche le nom de la ville
        		$response = $this->getObjects('Location','id, city','WHERE id = '.$realId);
        		foreach ($response->objects as $object){
        			$cityName = $object->fields->city;
        			break;
        		}
        		$response = $this->getObjects('Location','id, name, city, country','WHERE city = "'.$cityName.'"');
        		foreach ($response->objects as $object){
        			$result[] = $this->_newItem($object->fields->id, $object->fields->name, "Location",['city' => $object->fields->city,'country' => $object->fields->country]);
        		}
        	} else { // sinon, on retourne les devices
        		$response = $this->getObjects('PhysicalDevice','id, name','WHERE location_id = '.$itemId->id);
        		foreach ($response->objects as $object){
        			$result[] = $this->_newItem($object->fields->id, $object->fields->name, $object->class);
        		}
        	}
        } else if ($this->isFunctionalCI($itemId->prefix)){
        	// Trouver les groupes liés
        	if (($category == '*') || ($category == 'domain')){
        		// Le rajouter au domaine
        		$response = $this->getObjects("lnkGroupToCI", '*', 'WHERE ci_id = '.$itemId->id);
        		foreach ($response->objects as $object){
        			$result[] = $this->_newItem($object->fields->group_id, $object->fields->group_name, "Group");
        		}
        	}
        	if (($category == '*') || ($this->isFunctionalCI($category))){
        		// Trouver tous les items
        		$item = $this->getItems((object)['id'=>$aItemId])[0];
        		$response = $this->getRelated($item->class->name,$itemId->id,'down');
        		foreach ($response->objects as $object){
        			$result[] = $this->_newItem($object->fields->id, $object->fields->friendlyname, $object->class);
        		}
        	}
        	$response = $this->getObjects('lnkContactToFunctionalCI', 'contact_id,contact_name', 'WHERE functionalci_id = '.$itemId->id);
        	foreach($response->objects as $object){
        		$result[] = $this->_newItem($object->fields->contact_id, $object->fields->contact_name, 'Team');
        	}
        } else if ($itemId->prefix == 'data'){
        	$item = $this->getItems((object)['id'=>$aItemId])[0];
        	$schemaIdList = '(';
        	$first = true;
        	$schemas = array();
        	if ($item->class->name == 'Data'){
        		// Chercher les DatabaseSchema
        		$response = $this->getObjects('lnkGroupToCI','ci_id, ci_name, ci_id_finalclass_recall','WHERE group_id='.$itemId->id);
        		foreach($response->objects as $object){
        			$item = $this->_newItem($object->fields->ci_id, $object->fields->ci_name, $object->fields->ci_id_finalclass_recall);
        			if (!$first){
        				$schemaIdList .= ',';
        			}
        			$schemaIdList .= $object->fields->ci_id;
        			$first = false;
        			$schemas[] = $item;
        		}
        	} else if ($item->class->name == 'DatabaseSchema'){
        		$response = $this->getObjects('lnkGroupToCI','group_id, reason','WHERE ci_id='.$itemId->id);
        		$groupId = null;
        		foreach($response->objects as $object){
        			// S'ils s'agit bien d'une instance de ce groupe
        			if ($object->fields->reason == DATA_INSTANCE_REASON){
        				$groupId = $object->fields->group_id;
        				break;
        			}
        		}
        		if ($groupId != null){
        			$response = $this->getObjects('lnkGroupToCI','ci_id, ci_name, ci_id_finalclass_recall','WHERE group_id='.$groupId);
        			foreach($response->objects as $object){
        				$item = $this->_newItem($object->fields->ci_id, $object->fields->ci_name, $object->fields->ci_id_finalclass_recall);
        				if (!$first){
        					$schemaIdList .= ',';
        				}
        				$schemaIdList .= $object->fields->ci_id;
        				$first = false;
        				$schemas[] = $item;
        			}
        		}
        	} else {
        		error_log('getOverItems unknown class '.$item->class->name);
        	}
        	$schemaIdList .= ')';
        	// Si on n'a trouvé aucun groupe associé, on ajoute au moins l'instance elle même, cela signifie que cette instance n'est liée à aucun group 'Data' -> création hors Graf
        	if ((strlen($schemaIdList) == 2) && ($item->class->name == 'DatabaseSchema')){
        		$schemaIdList = '('.$itemId->id.')';
        	}
        	// Si on a trouvé au moins une instance de données, sinon, pour le moment, ce modèle n'est lié à personne
        	if (strlen($schemaIdList) != 2){
	        	error_log('$schemaIdList = '.$schemaIdList);
	        	// Retrouver tous les domaines liés à cette donnée
	        	if (($category == '*') || ($category == 'domain')){
	        		$response = $this->getObjects('lnkGroupToCI','group_id, group_name, reason','WHERE ci_id IN '.$schemaIdList);
		        	foreach($response->objects as $object){
		        		// S'il ne s'agit pas d'une instance de ce groupe
		        		if ($object->fields->reason != DATA_INSTANCE_REASON){
		        			$result[] = $this->_newItem($object->fields->group_id, $object->fields->group_name, 'Group');
		        		}
		        	}
	        	}
        	}
        	/*if (($category == '*') || ($this->isFunctionalCI($category)){
        		// Trouver tous les items
        		$item = $this->getItems((object)['id'=>$aItemId])[0];
        		$response = $this->getRelated($item->class->name,$itemId->id,'down');
        		foreach ($response->objects as $object){
        			$result[] = $this->_newItem($object->fields->id, $object->fields->friendlyname, $object->class);
        		}
        	}*/
        } else {
        	error_log('getOverItems other');
        }
        return $result;
    }
    private function getSubItems($aItemId,$category='*'){
    	//error_log("getSubItems $aItemId");
        $result = array();
        $itemId = $this->_splitItemId($aItemId);
        if ($itemId->prefix == "actor"){ // On recherche les items sous un acteur
            // Chercher tous les items liés à l'équipe
            $response = $this->getObjects("Team","function,cis_list","WHERE id = $itemId->id");
            foreach ($response->objects as $object){
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
        } else if ($itemId->prefix == 'location'){
        	if (strpos($itemId->id,'country_') !== false){ // si c'est un pays, il n'y a rien en dessous
        	} else if (strpos($itemId->id,'city_') !== false){ // Si c'est une ville, on retourne le pays
        		$pos = strpos($itemId->id,'_');
        		$realId = substr($itemId->id,$pos+1);
        		$response = $this->getObjects('Location','id, country','WHERE id = '.$realId);
        		foreach ($response->objects as $object){
        			$result[] = $this->_newItem('country_'.$object->fields->id, $object->fields->country, "Location",['city' => '','country' => $object->fields->country]);
        		}
        	} else { // sinon, on retourne la ville
        		$response = $this->getObjects('Location','id, city, country','WHERE id = '.$itemId->id);
        		foreach ($response->objects as $object){
        			$result[] = $this->_newItem('city_'.$object->fields->id, $object->fields->city, "Location",['city' => $object->fields->city,'country' => $object->fields->country]);
        		}
        	}
        } else {
        	$item = $this->getItems((object)['id'=>$aItemId])[0];
            if ($item->class->name == 'Data'){
                $item->class->name = 'Group';
            }
            $response = $this->getRelated($item->class->name,$itemId->id,'up');
            foreach ($response->objects as $object){
            	$item = $this->_newItem($object->fields->id, $object->fields->friendlyname, $object->class);
            	if ($item->id == $this->getGenericDBServerId()){
            		continue;
            	} else if ($item->id == $this->getGenericServerId()){
            		continue;
            	}
            	if (($category == "*") || ($item->category->name == $category)){
                	$result[] = $îtem;
            	}
            }
            if (($item->category->name == 'device') || ($item->class->name == 'Server')){
            	$response = $this->getObjects('PhysicalDevice', 'location_id', 'WHERE id = '.$itemId->id);
            	foreach ($response->objects as $object){
            		$location_id = $object->fields->location_id;
            		if ($location_id != 0){
            			$response = $this->getObjects('Location','id, name, city, country','WHERE id = '.$location_id);
            			foreach ($response->objects as $object){
            				$result[] = $this->_newItem($object->fields->id, $object->fields->name, "Location",['city' => $object->fields->city,'country' => $object->fields->country]);
            			}
            		}
            	}
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
    public function getItems($query){
    	if (!isset($query)){
    		$query = (object)array();
    	}
    	if (isset($query->category)){
    		$category=$query->category;
    	} else {
    		$category='*';
    	}
    	if (isset($query->class)){
    		$class=$query->class;
    	} else {
    		$class='*';
    	}
    	if (isset($query->name)){
    		$name=$query->name;
    	} else {
    		$name='*';
    	}
    	if (isset($query->id)){
    		$id=$query->id;
    	} else {
    		$id='*';
    	}
    	$result = array();
    	$andFilter = '';
    	$whereFilter = '';
    	if ($id != '*'){
    		$itemId = $this->_splitItemId($id);
    		$andFilter = " AND id = '".$itemId->id."'";
    		$whereFilter = " WHERE id = '".$itemId->id."'";
    		$category = $itemId->prefix;
    	} else if ($name != '*'){
    		$andFilter = " AND name LIKE '%".$name."%'";
    		$whereFilter = " WHERE name LIKE '%".$name."%'";
    	}
    	if ($class != "*") {
    		$classCategory = $this->getItemCategoryByClass($class);
	    	if ($category == "*"){ // et pas de catégorie, on la récupère
	    		$category = $classCategory->name;
    		} else {
    			// pas la peine de chercher les résultats 
    			if ($category != $classCategory) {
    				return $result;
    			}
    		}
    	}
    	if (($category == 'actor') || ($category == '*')){
        	$response = $this->getObjects('Team','id, name',"WHERE org_name = '$this->organisation'".$andFilter); // NB : org_name n'est pas standard, d'habitude c'est organization_name)
            foreach ($response->objects as $object){
                $result[]   = $this->_newItem($object->fields->id, $object->fields->name, 'Team');
            }
        }
        if (($category == 'domain') || ($category == '*')){
        	$response = $this->getObjects("Group", 'id, name, friendlyname, description','WHERE type="BusinessDomain"'.$andFilter);
            foreach ($response->objects as $object){
            	$item = $this->_newItem($object->fields->id, $object->fields->name, 'Group', ['area_id'=>$object->fields->description]);
                $item->area_id   = $object->fields->description;
                $result[] = $item;
            }
        }
        if (($category == 'data') || ($category == '*')){
        	$response = $this->getObjects("Group", 'id, name, friendlyname, description','WHERE type="DataModel"'.$andFilter);
            foreach ($response->objects as $object){
                $item = $this->_newItem($object->fields->id, $object->fields->name, "Data");
                $item->area_id   = $object->fields->description;
                $result[] = $item;
            }
            if ($id != '*'){
	           	$response = $this->getObjects('DatabaseSchema','id, name, description',"WHERE organization_name = '$this->organisation'".$andFilter);
	            foreach ($response->objects as $object){
	            	$item = $this->_newItem($object->fields->id, $object->fields->name, $object->class, ['description' => $object->fields->description]);
	            	if ($category != "*"){
	            		if ($item->category->name != $category){
	            			continue;
	            		}
	            	}
	            	$result[]   = $item;
	            }
            }
        }
        if (($category == 'service') || ($category == '*')){
        	$response = $this->getObjects('Service','id, name, description',"WHERE organization_name = '$this->organisation'".$andFilter);
            foreach ($response->objects as $object){
                $item = $this->_newItem($object->fields->id, $object->fields->name, $object->class);
                $item->area_id   = $object->fields->description;
                $result[]   = $item;
            }
        }
        if (($category == 'software') || ($category == '*')){
        	if ($class != '*'){
        		$response = $this->getObjects($class,'id, name',$whereFilter);
        	} else {
        		$response = $this->getObjects('Software','id, name, version, vendor, type',$whereFilter);
        	}
        	foreach ($response->objects as $object){
        		$result[]   = $this->_newItem($object->key, $object->fields->name, $object->class,['version' => $object->fields->version,'vendor' => $object->fields->vendor,'type' => $object->fields->type]);
        	}
        }
        if (($category == 'location') || ($category == '*')){
        	if ($id != '*'){
        		if (strpos($itemId->id,'country_') !== false){ // si c'est un pays
        			//error_log("getItems $id search country");
        			$pos = strpos($itemId->id,'_');
        			$realId = substr($itemId->id,$pos+1);
        			$response = $this->getObjects('Location','id, country','WHERE id = '.$realId);
        			foreach ($response->objects as $object){
        				$result[] = $this->_newItem('country_'.$object->fields->id, $object->fields->country, "Location",['city' => '','country' => $object->fields->country]);
        			}
        		} else if (strpos($itemId->id,'city_') !== false){ // Si c'est une ville
        			//error_log("getItems $id search city");
        			$pos = strpos($itemId->id,'_');
        			$realId = substr($itemId->id,$pos+1);
        			$response = $this->getObjects('Location','id, city, country','WHERE id = '.$realId);
        			foreach ($response->objects as $object){
        				$result[] = $this->_newItem('city_'.$object->fields->id, $object->fields->city, "Location",['city' => $object->fields->city,'country' => $object->fields->country]);
        			}
        		} else { // sinon, on retourne la ville
        			//error_log("getItems $id search location");
        			$response = $this->getObjects('Location','id, name, city, country','WHERE id = '.$itemId->id);
        			foreach ($response->objects as $object){
        				$result[] = $this->_newItem($object->fields->id, $object->fields->name, "Location",['city' => $object->fields->city,'country' => $object->fields->country]);
        			}
        		}
        	} else if ($name != '*'){
        		$response = $this->getObjects('Location','id, name, city, country',"WHERE org_name = '$this->organisation' AND name LIKE '%".$name."%'");
        		foreach ($response->objects as $object){
        			$result[]   = $this->_newItem($object->fields->id, $object->fields->name, $object->class,['city' => $object->fields->city,'country' => $object->fields->country]);
        		}
        		$response = $this->getObjects('Location','id, name, city, country',"WHERE org_name = '$this->organisation' AND city LIKE '%".$name."%'");
        		foreach ($response->objects as $object){
        			$result[]   = $this->_newItem('city_'.$object->fields->id, $object->fields->city, $object->class,['city' => $object->fields->city,'country' => $object->fields->country]);
        		}
        		$response = $this->getObjects('Location','id, name, city, country',"WHERE org_name = '$this->organisation' AND country LIKE '%".$name."%'");
        		foreach ($response->objects as $object){
        			$result[]   = $this->_newItem('country_'.$object->fields->id, $object->fields->country, $object->class,['city' => '','country' => $object->fields->country]);
        		}
        	} else {
	        	$response = $this->getObjects('Location','id, name, city, country',"WHERE org_name = '$this->organisation'".$andFilter);
	        	foreach ($response->objects as $object){
	        		$result[]   = $this->_newItem($object->fields->id, $object->fields->name, $object->class,['city' => $object->fields->city,'country' => $object->fields->country]);
	        	}
        	}
        }
        if (($category != 'actor') && ($category != 'data') && ($category != 'domain') && ($category != 'service') && ($category != 'software') && ($category != 'location')){
        	if ($class != '*'){
        		$response = $this->getObjects($class,'id, name, description',"WHERE organization_name = '$this->organisation'".$andFilter);
        	} else {
        		$response = $this->getObjects('FunctionalCI','id, name, description',"WHERE organization_name = '$this->organisation' AND finalclass NOT IN ('DBServer','Middleware','OtherSoftware','PCSoftware','WebServer','WebApplication')".$andFilter);
        	}
            
            foreach ($response->objects as $object){
                $item = $this->_newItem($object->fields->id, $object->fields->name, $object->class, ['description' => $object->fields->description]);
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
    public function addItemDocument($itemId,$documentId){
    	$item = $this->getItems((object)['id'=>$itemId])[0];
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
        $item = $this->getItems((object)['id'=>$itemId])[0];
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
    public function deleteDocument($documentId){
    	$this->deleteObject("DocumentNote", $documentId);
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
        $this->ITOP_CATEGORIES["location"] = $this->_newItemCategory("location");
        $this->ITOP_CATEGORIES["process"]  = $this->_newItemCategory("process");
        $this->ITOP_CATEGORIES["server"]   = $this->_newItemCategory("server");
        $this->ITOP_CATEGORIES["service"]  = $this->_newItemCategory("service");
        $this->ITOP_CATEGORIES["software"] = $this->_newItemCategory("software");
        $this->ITOP_CATEGORIES["solution"] = $this->_newItemCategory("solution");
        $this->ITOP_CLASSES = array();
        $this->_addItemClass("Team",               false,   $this->ITOP_CATEGORIES["actor"]);
        
        $this->_addItemClass("DatabaseSchema",     true,    $this->ITOP_CATEGORIES["data"]);    // Probleme 'dbserver_id'
        $this->_addItemClass("Data",               false,   $this->ITOP_CATEGORIES["data"]);
        
        $this->_addItemClass("Group",              false,   $this->ITOP_CATEGORIES["domain"],[(object)['label' => 'Area', 'name'=>'area_id','type'=>'area(strategic)','required'=>true]]);
        
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
        
        $this->_addItemClass("Location",    	   false,   $this->ITOP_CATEGORIES["location"],[
        		(object)['label' => 'City', 'name'=>'city','type'=>'string','required'=>true],
        		(object)['label' => 'Country', 'name'=>'country','type'=>'string','required'=>true]
        ]);
        
        $this->_addItemClass("BusinessProcess",    false,   $this->ITOP_CATEGORIES["process"]);
        
        $this->_addItemClass("Server",             false,   $this->ITOP_CATEGORIES["server"]);
        $this->_addItemClass("VirtualHost",        true,    $this->ITOP_CATEGORIES["server"]);
        $this->_addItemClass("VirtualMachine",     false,    $this->ITOP_CATEGORIES["server"],[
        		(object)['label' => 'Host', 'name'=>'virtualhost_id','type'=>'string','required'=>true],
        ]); // Problem 'virtualhost_id'
        $this->_addItemClass("Hypervisor",         false,   $this->ITOP_CATEGORIES["server"]);
        $this->_addItemClass("Farm",               false,   $this->ITOP_CATEGORIES["server"]);
        $this->_addItemClass("VirtualDevice",      true,    $this->ITOP_CATEGORIES["server"]);
        
        $this->_addItemClass("Service",            false,   $this->ITOP_CATEGORIES["service"]);
        
        
        $this->_addItemClass("Software",           false,   $this->ITOP_CATEGORIES["software"],[
        		(object)['label' => 'Type', 	'name'=>'type',		'type'=>'list(DBServer,Middleware,OtherSoftware,PCSoftware,WebServer)','required'=>true],
        		(object)['label' => 'Version',	'name'=>'version',	'type'=>'string','required'=>true],
        		(object)['label' => 'Vendor', 	'name'=>'vendor',	'type'=>'string','required'=>true]
        ]);
        
        $this->_addItemClass("DBServer",           true,   $this->ITOP_CATEGORIES["software"],[
        		(object)['label' => 'Version', 'name'=>'version','type'=>'string','required'=>true],
        		(object)['label' => 'Vendor',  'name'=>'vendor','type'=>'string','required'=>true]
        ]);
        $this->_addItemClass("Middleware",         true,   $this->ITOP_CATEGORIES["software"],[
        		(object)['label' => 'Version', 'name'=>'version','type'=>'string','required'=>true],
        		(object)['label' => 'Vendor',  'name'=>'vendor','type'=>'string','required'=>true]
        ]);
        $this->_addItemClass("MiddlewareInstance", true,   $this->ITOP_CATEGORIES["software"]);// Problem 'middleware_id'
        $this->_addItemClass("OtherSoftware",      true,   $this->ITOP_CATEGORIES["software"],[
        		(object)['label' => 'Version', 'name'=>'version','type'=>'string','required'=>true],
        		(object)['label' => 'Vendor',  'name'=>'vendor','type'=>'string','required'=>true]
        ]);
        $this->_addItemClass("PCSoftware",         true,   $this->ITOP_CATEGORIES["software"],[
        		(object)['label' => 'Version', 'name'=>'version','type'=>'string','required'=>true],
        		(object)['label' => 'Vendor',  'name'=>'vendor','type'=>'string','required'=>true]
        ]);
        $this->_addItemClass("SoftwareInstance",   true,   $this->ITOP_CATEGORIES["software"]);
        $this->_addItemClass("WebApplication",     true,   $this->ITOP_CATEGORIES["software"]);// Problem 'webserver_id'
        $this->_addItemClass("WebServer",          true,   $this->ITOP_CATEGORIES["software"],[
        		(object)['label' => 'Version', 'name'=>'version','type'=>'string','required'=>true],
        		(object)['label' => 'Vendor',  'name'=>'vendor','type'=>'string','required'=>true]
        ]);
        $this->_addItemClass("ApplicationSolution",false,   $this->ITOP_CATEGORIES["solution"]);
    }
    private function _addItemClass($className,$abstract,$category,$properties=[]){
        $class = new stdClass();
        $class->id          = $className;
        $class->name        = $className;
        $class->category    = $category;
        $class->abstract    = $abstract;
        $class->properties	= $properties;
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
    private function _newItem($id,$name,$className,$properties = array()){
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
        $category 				= $this->getItemCategoryByClass($className);
        $result->id    			= $category->name."_".$id;
        $result->name  			= $name;
        $result->class 			= new stdClass();
        $result->class->id 		= 1;
        $result->class->name 	= $className;
        $result->category 		= $category;
       	$result->properties 	= $properties;
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