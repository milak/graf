<?php
$configurationFileName = "/home/graf/configuration.json";
if (file_exists($configurationFileName)) {
    error_log("Loading configuration file '$configurationFileName'");
    $configurationFile = file_get_contents($configurationFileName);
    $configuration = json_decode($configurationFile);
} else {
    error_log("Warning configuration file '$configurationFileName' doesn't exist");
    $configuration = null;
}
function writeConfiguration(){
    global $configuration;
    global $configurationFileName;
    $configurationAsText = json_encode($configuration, JSON_PRETTY_PRINT);
    file_put_contents($configurationFileName,$configurationAsText);
}
?>