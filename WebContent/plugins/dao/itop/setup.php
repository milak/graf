<html>
<head>
  	<meta charset="utf-8">
  	<title>Connection du connecteur ITOP</title>
  	<link rel="stylesheet" href="../../css/graf.css">
 	<link rel="stylesheet" href="../../lib/pure/pure-min.css">
</head>
<body>
<?php
require("../daoutil.php");
require("dao.php");
$configuration = loadConfiguration();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($configuration != null) && (isset($configuration->itop))){
        $login      = $configuration->itop->login;
        $password   = $configuration->itop->login;
        $url        = $configuration->itop->url;
        $organisation = $configuration->itop->organisation;
        $version    = $configuration->itop->version;
    } else {
        $login      = "admin";
        $password   = "";
        $url        = "http://localhost/itop/webservices/rest.php";
        $version    = "1.3";
        $organisation = "";
    }
    $message = "";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url            = $_POST["url"];
    $login          = $_POST["login"];
    $password       = $_POST["password"];
    $organisation   = $_POST["organisation"];
    $version        = $_POST["version"];
    if ($configuration == null){
        $configuration = new stdClass();
    }
    $configuration->dao = "itop";
    if (!isset($configuration->itop)){
        $configuration->itop = new stdClass();
    }
    $configuration->itop->login     = $login;
    $configuration->itop->password  = $password;
    $configuration->itop->url       = $url;
    $configuration->itop->version   = $version;
    $configuration->itop->organisation   = $organisation;
    // Test the connection
    if (!$dao->connect()){
        $message = "Echec : ".$dao->error;
    } else {
        $response = $dao->getObjects('Organization','id','WHERE name = \''.$organisation.'\'');
        if (count($response->objects) == 0){
            $message = 'L\'organisation \''.$organisation.'\' n\'existe pas. ';
            $response = $dao->getObjects('Organization','name');
            if (count($response->objects) == 0){
                $message.= 'Aucune organisation n\'est enregistrée';
            } else {
                $message.= 'Les organisations suivantes existent : ';
                $first = true;
                foreach ($response->objects as $object){
                    if (!$first){
                        $message.= ', ';
                    }
                    $message.= '\''.$object->fields->name.'\'';
                    $first = false;
                }
            }
        } else {
            writeConfiguration($configuration);
            // Tester si le serveur ITOP possède les vues nécessaires
            // On vérifie que l'on a les vues disponibles
            $viewsRoot = "/home/graf/views";
            if (file_exists($viewsRoot)){
                $serviceFamily = $dao->getObjects("ServiceFamily","id","WHERE name = 'IT Services'");
                if (count($serviceFamily->objects) == 0){
                    error_log("Création de la famille de services IT Services");
                    $dao->createObject("ServiceFamily",array(
                        "name" => "IT Services"
                    ));
                }
                // Vérifier que l'on a tous les types de documents
                $documentTypes = array("Template","BPMN","TOSCA");
                foreach ($documentTypes as $type){
                    $response = $dao->getObjects("DocumentType","name",'WHERE name = \''.$type.'\'');
                    if (count($response->objects) == 0){
                        $dao->createObject("DocumentType",array(
                            "name" => $type
                        ));
                    }
                }
                // Vérification de la présence des vues
                $views = scandir($viewsRoot);
                foreach ($views as $viewFileName){
                    if ($viewFileName{0} == '.'){
                        continue;
                    }
                    $viewName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $viewFileName);
                    error_log('Vue '.$viewName);
                    $view = $dao->getViewByName($viewName);
                    if ($view == null) {
                        error_log("Création de la vue $viewName manquante");
                        $dao->createDocument($viewName,"Template",file_get_contents($viewsRoot."/".$viewFileName));
                    }
                }
                
            }
            $message = 'Mise à jour effectuée <a href="../../index.php" target="top">retour à GRAF</a>';
        }
    }
}
?>
<h1>Configuration du connecteur ITOP</h1>
<form class="pure-form pure-form-aligned" action="setup.php" method="post">
	<div class="pure-control-group">
		<label>Url</label><input name="url" type="text" value="<?php echo $url?>"/>
	</div>
	<div class="pure-control-group">
		<label>Login</label><input name="login" type="text" value="<?php echo $login?>"/>
	</div>
	<div class="pure-control-group">
		<label>Password</label><input name="password" type="password" value=""/>
	</div>
	<div class="pure-control-group">
		<label>Organisation</label><input name="organisation" type="text" value="<?php echo $organisation?>"/>
	</div>
	<div class="pure-control-group">
		<label>Version</label><input name="version" type="text" value="<?php echo $version?>"/>
	</div>
	<input type="submit"/>
</form>
<?php echo $message ?>
</body>
</html>