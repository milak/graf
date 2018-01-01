<?php
$configurationFileName = "/home/graf/configuration.json";
if (file_exists($configurationFileName)) {
    $configurationFile = file_get_contents($configurationFileName);
    $configuration = json_decode($configurationFile);
} else {
    $configuration = null;
}
function writeConfiguration(){
    global $configuration;
    global $configurationFileName;
    $configurationAsText = json_encode($configuration, JSON_PRETTY_PRINT);
    file_put_contents($configurationFileName,$configurationAsText);
}
?>