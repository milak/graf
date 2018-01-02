<?php
/**
 * Ce script propose des fonctions disponibles à tous les plugins dao chargés.
 * Il propose des classes et des fonctions pour parser les formats Process et Schema
 */
// Objet qui sera modifié une fois le plugin chargé
$dao = null;
// ***********************************
// Gestion du fichier de configuration
// ***********************************
$configuration = null;
$configurationFileName = null;
/**
 * Charger la configuration
 * @return l'objet configuration
 */
function loadConfiguration(){
    global $configurationFileName;
    $configurationFileName = "/home/graf/configuration.json";
    if (file_exists($configurationFileName)) {
        error_log("Loading configuration file '$configurationFileName'");
        $configurationFile = file_get_contents($configurationFileName);
        $configuration = json_decode($configurationFile);
    } else {
        error_log("Warning configuration file '$configurationFileName' doesn't exist");
        $configuration = null;
    }
    return $configuration;
}
/**
 * Sérialise la configuration
 */
function writeConfiguration($configuration){
    global $configurationFileName;
    $configurationAsText = json_encode($configuration, JSON_PRETTY_PRINT);
    file_put_contents($configurationFileName,$configurationAsText);
}
/**
 * Charge le plugin
 * @param string $daoName
 */
function loadPlugin($daoName){
    global $dao;
    // TODO trouver comment ne plus avoir à ajouter en dur /graf. Problème de conf apache ?
    $root = realpath($_SERVER["DOCUMENT_ROOT"]);
    $pluginFile = $root."/dao/".$daoName."/dao.php";
    if (!file_exists($pluginFile)) {
        $root = realpath($_SERVER["DOCUMENT_ROOT"])."/graf";
        $pluginFile = $root."/dao/".$daoName."/dao.php";
    }
    if (!file_exists($pluginFile)) {
        die("Plugin ".$daoName." not installed. File ".$pluginFile." doesn't exist");
        exit();
    }
    // Inclure le plugin afin de charger la classe $DAO associée au plugin
    require($pluginFile);
    if ($dao == null){
        die("Plugin ".$daoName." not well installed. Object \$dao not initialized.");
        exit();
    }
}
// ****************
// Gestion des vues
// ****************
function recursive_parseViewToArray(&$list, $parent, $currentArea){
    $area = new Area();
    $area->id        = $currentArea["name"];
    $area->name      = $currentArea["label"];
    $area->code      = $currentArea["name"];
    $area->parent_id = null;
    if (isset($currentArea["display"])){
        $area->display   = $currentArea["display"];
    } else {
        $area->display   = 'vertical';
    }
    $area->position  = 0;
    $area->parent    = $parent;
    $list[$area->id] = $area;
    if (isset($currentArea["children"])) {
        foreach($currentArea["children"] as $child){
            $subarea = recursive_parseViewToArray($list, $area, $child);
            $area->subareas[] = $subarea;
        }
    }
    return $area;
}
function parseViewToArray($view){
    $result = array();
    recursive_parseViewToArray($result, null, $view);
    return $result;
}
/**
 * Classe Area
 * 
 * Correspond à une zone à afficher dans une vue
 *
 */
class Area {
    public $id;
    public $name;
    public $code;
    public $display;
    public $position;
    public $parent = null;
    public $parent_id;
    public $needed = false;
    public $elements  = array();
    public $subareas  = array();
    public function addElement($element){
        $this->elements[] = $element;
        $this->setNeeded();
    }
    public function setNeeded(){
        $this->needed = true;
        if ($this->parent != null){
            $this->parent->setNeeded();
        }
    }
}
/**
 * Class Parseable
 *
 * Classe abstraite qui sera réutilisée pour parser des formats de données dans le but de fournis des éléments à afficher dans la vue.
 * 
 */
abstract class Parseable {
    protected $xml;
    public $elements;
    public function __construct(){
        if (func_num_args() == 0){
            $this->xml = $this->defaultContent();
            $this->elements = array();
        } else if (func_num_args() == 1){
            $this->__constructWithXML(func_get_args()[0]);
        } else {
            throw new Exception("IllegalArgumentException too many arguments");
        }
    }
    public function __constructWithXML($xml){
        $this->xml = $xml;
        $this->parse();
    }
    public function getXML(){
        return $this->xml;
    }
    protected abstract function defaultContent();
    protected abstract function parse();
    protected function addLink($link){
        $elementFrom = $this->elements[$link->from_id];
        if ($elementFrom == null){
            error_log ("Step (id=$link->from_id) not found, skipped");
            return;
        }
        $elementTo = $this->elements[$link->to_id];
        if ($elementTo == null){
            error_log ("Step (id=$link->to_id) not found, skipped");
            return;
        }
        $link->to 	= $elementTo;
        $elementFrom->links[] = $link;
    }
}

/**
 * Class Process
 * 
 * Permet de parser Process dans un format BPMN pour l'afficher dans une vue
 * 
 */
//const DEFAULT_PROCESS_CONTENT = '&lt;bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"&gt;&lt;bpmn:startEvent name="" id="1"/&gt;&lt;/bpmn:definitions&gt;';
const DEFAULT_PROCESS_CONTENT = '<bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"><bpmn:startEvent name="" id="1"/></bpmn:definitions>';
class Process extends Parseable {
    protected function defaultContent(){
        return DEFAULT_PROCESS_CONTENT;
    }
    protected function parse(){
        $xml = new SimpleXMLElement($this->xml);
        $xml->registerXPathNamespace('prefix', 'http://www.omg.org/spec/BPMN/20100524/MODEL');
        $children = $xml->xpath("//prefix:*");
        $this->elements = array();
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
            $this->elements[$step->id] = $step;
        }
        // Raccrocher toutes les étapes entre elles
        foreach ($links as $link){
            $this->addLink($link);
        }
    }
}
/**
 * Class Schéma
 *
 * Permet de parser un Schéma (dans un format BPMN-like pour le moment) pour l'afficher dans une vue
 *
 */
//const DEFAULT_SCHEMA_CONTENT = '&lt;bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"&gt;&lt;/bpmn:definitions&gt;';
const DEFAULT_SCHEMA_CONTENT = '<bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"></bpmn:definitions>';
class Schema extends Parseable {
    protected function defaultContent(){
        return DEFAULT_SCHEMA_CONTENT;
    }
    protected function parse(){
        if ($this->xml == null){
            $this->xml = DEFAULT_SCHEMA_CONTENT;
        }
        $xml = new SimpleXMLElement($this->xml);
        $xml->registerXPathNamespace('BPMN', 'http://www.omg.org/spec/BPMN/20100524/MODEL');
        $xml->registerXPathNamespace('DIAG', 'http://www.omg.org/spec/BPMN/20100524/DI');
        $this->elements = array();
        $links = array();
        $children = $xml->xpath("//BPMN:*");
        foreach($children as $node){
            $name = $node->getName();
            if ($name == "definitions"){
                continue;
            } else if ($name == "process"){
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
                continue;
            }
            $step->id    = "".$node["id"];
            $step->name  = "".$node["name"];
            $step->links = array();
            $this->elements[$step->id] = $step;
            $result[] = $step;
        }
        // Parcourir la représentation graphique pour identifier les lanes
        $children = $xml->xpath("//BPMN:*");
        foreach($children as $node){
            $name = $node->getName();
            
        }
        // Raccrocher toutes les étapes entre elles
        foreach ($links as $link){
            $this->addLink($link);
        }
    }
}
/**
 * Classe abstraite à implementer pour représenter un dao.
 */
interface IDAO {
    public function connect();
    
    public function getDomains();
    public function getDomainById($id);
    public function createDomain($name,$area_id);
    public function deleteDomain($id);
    
    public function getViews();
    public function getViewByName($name);
    
    public function getItemCategories();
    public function getItemsByCategory($category);
    public function getItems();
    public function getItemById($id);
    public function getItemsByServiceId($serviceId);
    public function getItemsByDomainId($domainId,$class="*");
    
    public function disconnect();
}
?>