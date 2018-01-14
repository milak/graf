var currentSolutionId = null;
function refreshSolutionLists(){
	$.getJSON("api/element.php?category_name=solution", function(result){
		var elements = result.elements;
		var options = "<option value='null' selected>--choisir une solution--</option>";
		for (var i = 0; i < elements.length; i++){
			var element = elements[i];
			options += '<option value="'+element.id+'">'+element.name+'</option>';
		}
		$('#search_solution_form_list').html(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Impossible de charger les solutions</h1>"+textStatus+ " : " + error);
	});
}
function refreshLogicalAreaList(){
	$.getJSON( "api/view.php?view=logical", function(result) {
		$('#search_item_form_area').html(buildAreaList(result.view));
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function displaySolution(solutionId){
	if ($("#logic_toolbox").is(":hidden")){
		hideToolBox();
		$("#logic_toolbox").show();
	}
	if (solutionId == null){
		clearFrame();
		$("#logic_create_software_button").button("disable");
		$("#logic_create_device_button").button("disable");
		$("#logic_create_service_button").button("disable");
		$("#logic_create_data_button").button("disable");
		$("#logic_create_instance_button").button("disable");
		$("#logic_edit_button").button("disable");
		currentSolutionId = null;
	} else {
		changeImage("views/view_logique.php?id="+solutionId);
		$("#logic_create_software_button").button("enable");
		$("#logic_create_device_button").button("enable");
		$("#logic_create_service_button").button("enable");
		$("#logic_create_data_button").button("enable");
		$("#logic_create_instance_button").button("enable");
		$("#logic_edit_button").button("enable");
		currentSolutionId = solutionId;
		loadSolutionScript(solutionId);
	}
}
function loadSolutionScript(solutionId){
	$.ajax({
		type : "GET",
		url  : "api/element.php?document=true&type=TOSCA&id="+solutionId,
		dataType : "text",
		success : function (data){
			$("#solution_script_editor_form_text").val(data);
		}
	});
}
function saveSolutionScript(itemId){
	var script = $("#solution_script_editor_form_text").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/element.php",
		data	: {
			"id"		: itemId,
			"document"	: script,
			"type"		: "TOSCA"
		},
		dataType: "text",
		success	: function( data ) {
			displaySolution(itemId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function searchSolution(){
	var selectedSolution = $("#search_solution_form_list").val();
	if (selectedSolution == "null"){
		return;
	}
	displaySolution(selectedSolution);
}
function showSolutionContext(id){
	$.getJSON( "api/element.php?id="+id, function(result) {
		var element = result.elements[0];
		var html = "<p>Solution</p>";
		html += "<b>Nom</b> : "+element.name+"<br/><br/>";
		html += "<hr/>";
		html += " <button onclick='hidePopup();displaySolution(\""+id+"\")'><img src='images/63.png'/> ouvrir</button>";
		html += " <button onclick='hidePopup();deleteElement(\""+id+"\")'><img src='images/14.png'/> supprimer</button>";
		html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
		showPopup("Détail",html);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function editSolutionScript(){
	$("#solution_script_editor_form").dialog({"modal":false,"title":"Edition de la solution","minWidth":500,"minHeight":500});
	try{
		$("#solution_script_editor_form").dialog("update");
	}catch(exception){}
}