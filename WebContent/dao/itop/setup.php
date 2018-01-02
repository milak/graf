<html>
<head>
  	<meta charset="utf-8">
  	<title>Connection du connecteur ITOP</title>
  	<link rel="stylesheet" href="../../css/graf.css">
 	<link rel="stylesheet" href="../../lib/pure/pure-min.css">
</head>
<body>
<?php
require("dao.php");
require("../daoutil.php");
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($configuration != null) && (isset($configuration->itop))){
        $login      = $configuration->itop->login;
        $password   = $configuration->itop->login;
        $url        = $configuration->itop->url;
        $organisation = $configuration->itop->organisation;
        $version    = $configuration->itop->version;
    } else {
        $login      = "";
        $password   = "";
        $url        = "";
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
        writeConfiguration($configuration);
        $message = 'Mise à jour effectuée <a href="../../index.php" target="top">retour à GRAF</a>';
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