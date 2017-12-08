<?php
/**
 * Dao chapeau qui va charger le dao adapté à la configuration
 */
$configurationFile = file_get_contents("/home/graf/configuration.json");
$configuration = json_decode($configurationFile);
require("../dao/".$configuration->dao."/dao.php");
?>