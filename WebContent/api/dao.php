<?php
/**
 * Ce script charge la configuration et active le plugin dao correspondant à la configuration
 */
require_once('daoutil.php');
$configuration = loadConfiguration();
// Si le chargement de la configuration a échoué, on charge le setup
if ($configuration == null) {
    error_log("No configuration loaded, redirecting to setup.php");
    header('Location: /setup.php');
    exit();
}
/**
 * Dao chapeau qui va charger le dao adapté à la configuration
 */
function getDAO($daoType){
	global $configuration;
	return loadPlugin($configuration->dao->{$daoType});
}
?>