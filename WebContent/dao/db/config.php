<html>
<head>
  	<meta charset="utf-8">
  	<title>Connection du connecteur ITOP</title>
  	<link rel="stylesheet" href="../../css/graf.css">
 	<link rel="stylesheet" href="../../lib/pure/pure-min.css">
</head>
<body>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {?>
<h1>Configuration du connecteur DB</h1>
<form class="pure-form pure-form-aligned" action="config.php" method="post">
	<div class="pure-control-group">
		<label>URL</label><input name="url" type="text"/>
	</div>
	<div class="pure-control-group">
		<label>Login</label><input name="login" type="text"/>
	</div>
	<div class="pure-control-group">
		<label>Password</label><input name="password" type="password"/>
	</div>
	<div class="pure-control-group">
		<label>Instance</label><input name="instance" type="text"/>
	</div>
	<input type="submit"/>
</form>
<?php
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url            = $_POST["url"];
    $login          = $_POST["login"];
    $password       = $_POST["password"];
    $instance       = $_POST["instance"];
    ?>
	Mise à jour effectuée
<?php
}
?>
</body>
</html>