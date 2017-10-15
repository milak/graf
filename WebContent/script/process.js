var currentProcessId;
function displayProcess(processId){
	hideToolBox();
	currentProcessId = processId;
	$("#process_toolbox").show();
	if (processId == null){
		clearFrame();
		$("#process_create_step_button").button("disable");
	} else {
		changeImage("views/view_process.php?id="+processId);
		$("#process_create_step_button").button("enable");
	}
}
function createProcess(domainId){
	if ((domainId == null) || (domainId == "null")){
		return;
	}
	$("#process_create_form_domain_id").val(domainId);
	$("#process_create_form").dialog({"modal":true,"title":"Création d'un processus","minWidth":500});
}
function doCreateProcess(){
	var name 	= $("#process_create_form_name").val();
	var description = $("#process_create_form_description").val();
	var domain_id 	= $("#process_create_form_domain_id").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/process.php",
		data	: {
			"name"		: name,
			"description"	: description,
			"domain_id"	: domain_id},
		dataType: "text",
		success	: function( data ) {
			$("#process_create_form").dialog("close");
			displayBusiness(null);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function deleteProcess(processId){
	if (!confirm("Etes-vous sûr de vouloir supprimer le processus ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/process.php?id="+processId,
		dataType: "text",
		success	: function( data ) {
   			alert("Processus supprimé");
			displayBusiness(null);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function createProcessStep(){
	$("#process_step_create_form_process_id").val(currentProcessId);
	$("#process_step_create_form_submit").prop("disabled",true);
	$("#process_step_create_form_name").val("");
	$("#process_step_create_form_type").val("null");
	onProcessStepCreateFormTypeChange();
	$("#process_step_create_form").dialog({"modal":true,"title":"Création d'une étape","minWidth":500});
}
function doCreateProcessStep(){
	var name = $("#process_step_create_form_name").val().trim();
	if (name == ""){
		alert("Le nom est obligatoire");
		return;
	}
	var type = $("#process_step_create_form_type").val();
	var process_id = $("#process_step_create_form_process_id").val();
	var actor_id = $("#process_step_create_form_actor_list").val();
	if ((type == "ACTOR") && (actor_id == "null")){
		alert("Vous devez choisir un acteur");
		return;
	}
	var sub_process_id = $("#process_step_create_form_process_list").val();
	if ((type == "SUB-PROCESS") && (sub_process_id == "null")){
		alert("Vous devez choisir un processus");
		return;
	}
	var service_id = $("#process_step_create_form_service_list").val();
	if ((type == "SERVICE") && (service_id == "null")){
		alert("Vous devez choisir un service");
		return;
	}
	$.ajax({
		type 	: "POST",
		url 	: "api/process_step.php",
		data	: {
			"name"			 : name,
			"process_id"	 : process_id,
			"type"			 : type,
			"actor_id"		 : actor_id,
			"sub_process_id" : sub_process_id,
			"service_id"	 : service_id},
		dataType: "text",
		success	: function( data ) {
			$("#process_step_create_form").dialog("close");
			displayProcess(process_id);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function onProcessStepCreateFormNameChange(){
	var ok = true;
	var name = $("#process_step_create_form_name").val().trim();
	if (name == ""){
		ok = false;
	}
	var type = $("#process_step_create_form_type").val();
	if (type == "null"){
		ok = false;
	}
	if (ok) {
		$("#process_step_create_form_submit").prop("disabled",false);
	} else {
		$("#process_step_create_form_submit").prop("disabled",true);
	}
}
function onProcessStepCreateFormTypeChange(){
	var type = $("#process_step_create_form_type").val();
	$("#process_step_create_form_process").hide();
	$("#process_step_create_form_service").hide();
	$("#process_step_create_form_actor").hide();
	if (type == "SERVICE"){
		$("#process_step_create_form_service").show();
	} else if (type == "SUB-PROCESS"){
		$("#process_step_create_form_process").show();
	} else if (type == "ACTOR"){
		$("#process_step_create_form_actor").show();
	}
	onProcessStepCreateFormNameChange();
}
function onProcessStepCreateFormActorListChange(){
	var name = $("#process_step_create_form_actor_list option:selected").text();
	$("#process_step_create_form_name").val(name);
	onProcessStepCreateFormNameChange();
}
function onProcessStepCreateFormServiceListChange(){
	var name = $("#process_step_create_form_service_list option:selected").text();
	$("#process_step_create_form_name").val(name);
	onProcessStepCreateFormNameChange();
}
function onProcessStepCreateFormProcessListChange(){
	var name = $("#process_step_create_form_process_list option:selected").text();
	$("#process_step_create_form_name").val(name);
	onProcessStepCreateFormNameChange();
}
function deleteProcessStep(processStepId){
	if (!confirm("Etes-vous sûr de vouloir supprimer cette étape ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/process_step.php?id="+processStepId,
		dataType: "text",
		success	: function( data ) {
			displayProcess(currentProcessId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function showProcessStepContext(id){
	$("#edit_process_step_form_open_process").hide();
	$("edit_process_step_form_delete").hide();
	$.getJSON( "api/step.php?id="+id, function(result) {
		var step = result.steps[0];
		$("#edit_process_step_form_id").val(id);
		$("#edit_process_step_form_name").val(step.name);
		var type = step.step_type_name;
		$("#edit_process_step_form_type").val(type);
		if (type == "SUB-PROCESS") {
			$("#edit_process_step_form_sub_process_id").val(step.sub_process_id);
			$("#edit_process_step_form_open_process").show();
		}
		if (step.step_type_name != "START") {
			$("#edit_process_step_form_delete").show();
		}
		$("#edit_process_step_form").dialog({"modal":true,"title":"Edition d'une étape","minWidth":500});
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function refreshProcessLists(){
	$.getJSON("api/process.php", function(result){
		var process = result.process;
		var options = "<option value='null' selected>--choisir un processus--</option>";
		for (var i = 0; i < process.length; i++){
			var proc = process[i];
			options += '<option value="'+proc.id+'">'+proc.name+'</option>';
		}
		$('#process_step_create_form_process_list').html(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Impossible de charger les processus</h1>"+textStatus+ " : " + error);
	});
}