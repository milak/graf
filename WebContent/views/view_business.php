<?php
// header('Content-Type: image/svg+xml'); //ne fonctionne pas car le type mime n'est pas reconnu
require ("../svg/header.php");
require ("../dao/dao.php");
require ("util.php");
require ("../svg/body.php");
$dao->connect();
$areas = $dao->getViewByName("business");
$label = "";
if (isset($_GET["id"])) {
    $id = $_GET["id"];
} else {
    displayErrorAndDie('Need "id" argument');
}
$item = $dao->getItemById($id);
$items = $dao->getRelatedItems($id,"*","down");

// Chargement des processus
$actors = array();
$process = array();
$services = array();
$rootarea       = $areas["root"];
$rootarea->name = $rootarea->name . " " . $item->name;
$processarea    = $areas["process"];
$domainarea     = $areas["domain"];
$servicearea    = $areas["service"];
$actorarea      = $areas["actor"];
$resourcesarea  = $areas["resource"];
$dataarea       = $areas["data"];
$solutionarea   = $areas["solution"];
foreach ($items as $item) {
    $obj = new stdClass();
    $obj->id = $item->id;
    $obj->type = $item->category->name;
    $obj->name = $item->name;
    $obj->links = array();
    $obj->display = new stdClass();
    if ($item->category->name == "actor") {
        $obj->display->class = "actor";
        if ($actorarea != null) {
            $actorarea->addElement($obj);
        }
    } else if ($item->category->name == "data") {
        $obj->display->class = "component_data";
        if ($dataarea != null) {
            $dataarea->addElement($obj);
        }
    } else if ($item->category->name == "device") {
        $obj->display->class = "component_device";
        if ($resourcesarea != null) {
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "data") {
        $obj->display->class = "component_device";
        if ($resourcesarea != null) {
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "process") {
        if ($processarea != null) {
            $obj                    = new stdClass();
            $obj->id                = $item->id;
            $obj->type              = $item->category->name;
            $obj->name              = $item->name;
            $obj->links             = array();
            $obj->display           = new stdClass();
            $obj->display->class    = "process_" . strtolower("sub-process");
            $processarea->addElement($obj);
        }
        $documents = $dao->getItemDocuments($item->id, "BPMN");
        if (count($documents) != 0) {
            $document = $documents[0];
            $content = $dao->getDocumentContent($document->id);
            $steps = (new Process($content))->elements;
            foreach ($steps as $step) {
                $type_name = $step->type_name;
                if (($type_name == "START") || ($type_name == "END")) {
                    // SKIP
                } else if ($type_name == "ACTOR") {
                    /*
                     * $obj = new stdClass();
                     * $obj->id = $row["step_id"];
                     * $obj->type = "box";
                     * $obj->display           = new stdClass();
                     * $obj->display->class = "process_".strtolower($type_name);
                     * $obj->name = $row["step_name"];
                     * $obj->links = array();
                     * $area_actor->elements[] = $obj;
                     */
                } else if ($type_name == "SERVICE") {
                    /*
                     * $obj = new stdClass();
                     * $obj->id = $row["service_id"];
                     * $obj->type = "service";
                     * $obj->display           = new stdClass();
                     * $obj->display->class = "process_".strtolower($type_name);
                     * $obj->name = $row["service_id"]."-".$row["step_name"];
                     * $obj->links = array();
                     * $area_service->elements[] = $obj;
                     */
                } else if ($type_name == "SUB-PROCESS") {
                    if ($processarea != null) {
                        $obj                    = new stdClass();
                        $obj->id                = $step->id;
                        $obj->type              = "process";
                        $obj->name              = $step->name;
                        $obj->links             = array();
                        $obj->display           = new stdClass();
                        $obj->display->class    = "process_" . strtolower($type_name);
                        $processarea->addElement($obj);
                    }
                }
            }
        }
    } else if ($item->category->name == "server") {
        $obj->display->class = "server";
        if ($resourcesarea != null) {
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "service") {
        if ($servicearea != null) {
            $obj = new stdClass();
            $obj->id = $item->id;
            $obj->type = "service";
            $obj->display  = new stdClass();
            $obj->display->class = "process_service";
            $obj->name = $item->name;
            $obj->links = array();
            $servicearea->addElement($obj);
        }
    } else if ($item->category->name == "software") {
        $obj->display->class = "component_software";
        if ($resourcesarea != null) {
            $resourcesarea->addElement($obj);
        }
    } else if ($item->category->name == "solution") {
        $obj->display->class = "component_software";
        if ($solutionarea != null) {
            $solutionarea->addElement($obj);
        }
    } else if ($item->category->name == "domain") {
        $obj->display->class = "component_software";
        if ($domainarea != null) {
            $domainarea->addElement($obj);
        }
    } else {
        error_log("Category non prévue ".$item->category->name);
    }
}
// Afficher le résultat
display(array($rootarea));
$dao->disconnect();
require ("../svg/footer.php");
?>