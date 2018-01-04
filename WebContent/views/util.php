<?php
/**
 * Classes utiles aux vues
 */
class Element {
    public $id;
    public $name;
    public $label;
    public $type;
    public $class;
    public $links = array();
    public function addLink($link){
        $this->links[] = $link;
    }
    public function getLinks(){
        return $this->links;
    }
}
class Link {
    public $from;
    public $to;
    public $label;
    public function __construct($from,$to,$label = ""){
        /*if (func_num_args() != 2){
            throw new Exception("WrongNumberArgumentException : two arguments needed : $labe");
        }*/
        $this->from     = $from;
        $this->to       = $to;
        $this->label    = $label;
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
 * 
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
 * Class TOSCA
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
                continue;
            } else if ($name == "endEvent"){
                continue;
            } else if ($name == "exclusiveGateway"){
                continue;
            } else if ($name == "parallelGateway"){
                continue;
            } else if ($name == "userTask"){
                $element = new stdClass();
                $element->category = "actor";
            } else if ($name == "serviceTask"){
                $element = new stdClass();
                $element->category = "service";
            } else if ($name == "task"){
                $element = new stdClass();
                $element->category = "solution";
            } else if ($name == "scriptTask"){
                $element = new stdClass();
                $element->category = "software";
            } else if ($name == "subProcess"){
                $element = new stdClass();
                $element->category = "solution";
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
            $element->id    = "".$node["id"];
            $element->name  = "".$node["name"];
            $element->links = array();
            $this->elements[$element->id] = $element;
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
function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}
?>