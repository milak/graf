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
    if (($configuration != null) && (isset($configuration->db))){
        $login      = $configuration->db->login;
        $password   = $configuration->db->login;
        $instance   = $configuration->db->instance;
        $host       = $configuration->db->host;
    } else {
        $login      = "";
        $password   = "";
        $instance   = "";
        $host       = "";
    }
    $message = "";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host           = $_POST["host"];
    $login          = $_POST["login"];
    $password       = $_POST["password"];
    $instance       = $_POST["instance"];
    if ($configuration == null){
        $configuration = new stdClass();
    }
    $configuration->dao = "db";
    if (!isset($configuration->db)){
        $configuration->db = new stdClass();
    }
    $configuration->db->login = $login;
    $configuration->db->password = $password;
    $configuration->db->instance = $instance;
    $configuration->db->host    = $host;
    // Test the connection
    if (!$dao->connect()) {
       $message = "Impossible de se connecter $dao->error";
    } else { 
       writeConfiguration($configuration);
       $message = 'Mise à jour effectuée <a href="../../index.php" target="top">retour à GRAF</a>';
    }
}
?>
<h1>Configuration du connecteur DB</h1>
<form class="pure-form pure-form-aligned" action="setup.php" method="post">
	<div class="pure-control-group">
		<label>Host</label><input name="host" type="text" value="<?php echo $host?>"/>
	</div>
	<div class="pure-control-group">
		<label>Login</label><input name="login" type="text" value="<?php echo $login?>"/>
	</div>
	<div class="pure-control-group">
		<label>Password</label><input name="password" type="password" value=""/>
	</div>
	<div class="pure-control-group">
		<label>Instance</label><input name="instance" type="text" value="<?php echo $instance?>"/>
	</div>
	<input type="submit"/>
</form>
<?php echo $message ?>
</body>
</html>