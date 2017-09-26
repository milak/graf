<?php
$configurationFile = file_get_contents("/home/graf/configuration.json");
$configuration = json_decode($configurationFile);
// Connection à la BDD
$db = new mysqli($configuration->db->host, $configuration->db->user, $configuration->db->password, $configuration->db->instance);
if (!$db) {
   displayErrorAndDie('Impossible de se connecter : ' . $db->connect_error);
}
?>