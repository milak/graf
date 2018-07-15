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
        if (JSON_ERROR_NONE == json_last_error()){
        	error_log("Configuration file '$configurationFileName' loaded");
    	} else { 
         	error_log("Warning, unable to read configuration file : ".json_last_error()." - ".json_last_error_msg());
    	}
    } else {
        error_log("Warning, configuration file '$configurationFileName' doesn't exist");
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
    $root = realpath($_SERVER["DOCUMENT_ROOT"]);
    $pluginFile = $root."/plugins/dao/".$daoName."/dao.php";
    if (!file_exists($pluginFile)) {
    	$root = realpath($_SERVER["DOCUMENT_ROOT"])."/graf";  // TODO trouver comment ne plus avoir à ajouter en dur /graf. Problème de conf apache ?
        $pluginFile = $root."/plugins/dao/".$daoName."/dao.php";
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
    return $dao;
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
 * Classe abstraite à implementer pour représenter un dao.
 */
interface IDAO {
    public function connect();
    
    public function getViews();
    public function getViewByName($name);
    
    public function getItemCategories();
    public function getItems($query);
    
    public function getDocuments($query);
    public function disconnect();
}
?>