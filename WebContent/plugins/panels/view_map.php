<?php
require("../../api/dao.php");
$dao = getDAO("items");
$dao->connect();
$map = 'world';
if (isset($_GET['map'])){
	$map = $_GET['map'];
}
require('../../images/maps/'.$map.'.svg');
$allReadyBrowsed = array();
function recursiveSearchLocation($id){
	global $allReadyBrowsed;
	global $dao;
	if (isset($allReadyBrowsed[$id])){
		return;
	}
	$allReadyBrowsed[$id] = true;
	$items = $dao->getRelatedItems($id,'*','down');
	foreach ($items as $item){
		error_log("====>".$item->name);
		if ($item->category->name == "location"){
			echo '$("[title=\''.$item->name.'\']").css({ fill: "#DD00DD" });';
		}
		recursiveSearchLocation($item->id);
	}
}
echo "<script>";
if (isset($_GET["itemId"])){
	$items = $dao->getItems((object)['id'=>$_GET["itemId"]]);
	foreach ($items as $item){
		if ($item->category->name == "location"){
			echo '$("path[title=\''.$item->name.'\']").css({ fill: "#DD00DD" });';
		}
	}
	recursiveSearchLocation($_GET["itemId"]);
}?>
$("path").on("click",function(event){
	var name = $(event.target).attr("title");
	$.getJSON( "api/item.php?class_name=Location&name="+name, function(result) {
		if (result.code != 0){
			sendMessage("error",i18next.t("message.no_item_information")+" : "+result.message);
		} else if (result.objects.length > 0){
			global.item.open(result.objects[0].id);
		} else {
			sendMessage("warning","No item located in " + name);
		}
	});
});
<?php
echo "</script>";
$dao->disconnect();
?>