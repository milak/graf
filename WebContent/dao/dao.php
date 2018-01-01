<?php
require_once('daoutil.php');
if ($configuration == null) {
    header('Location: setup.php');
    exit();
}
/**
 * Dao chapeau qui va charger le dao adapté à la configuration
 */
// TODO trouver comment ne plus avoir à ajouter en dur /graf. Problème de conf apache ?
$root = realpath($_SERVER["DOCUMENT_ROOT"])."/graf";
$pluginFile = $root."/dao/".$configuration->dao."/dao.php";
if (!file_exists($pluginFile)) {
    die("plugin ".$configuration->dao." not installed. File ".$plugin." doesn't exist");
    exit();
}
require($root."/dao/".$configuration->dao."/dao.php");
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
}?>