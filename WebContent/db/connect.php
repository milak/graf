<?php
// Connection à la BDD
$db = new mysqli('localhost', 'root', '', 'carto');
if (!$db) {
   displayErrorAndDie('Impossible de se connecter : ' . $db->connect_error);
}
?>
