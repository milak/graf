<?php
/**
 * Ce script charge la configuration et active le plugin dao correspondant à la configuration
 */
require_once('daoutil.php');
// Si le chargement de la configuration
$configuration = loadConfiguration();
if ($configuration == null) {
    error_log("No configuration loaded, redirecting to setup.php");
    header('Location: /setup.php');
    exit();
}
/**
 * Dao chapeau qui va charger le dao adapté à la configuration
 */
loadPlugin($configuration->dao);
?>