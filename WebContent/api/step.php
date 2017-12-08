<?php
header("Content-Type: application/json");
require("../dao/dao.php");
$dao->connect();
$db = $dao->getDB();
$dao->disconnect();
?>