function displayProcess(processId){
	hideToolBox();
	$("#process_toolbox").show();
	if (processId == null){
		clearFrame();
		$("#process_create_step_button").button("disable");
	} else {
		changeImage("views/view_process.php?id="+processId);
		$("#process_create_step_button").button("enable");
	}
}
function createProcess(){
	var domainId = $("#domainSelected").val();
	if ((domainId == null) || (domainId == "null")){
		return;
	}
	$("#process_create_form_domain_id").val(domainId);
	$("#process_create_form").dialog({"modal":true,"title":"Création d'un processus"});
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
function createStep(){
	alert("Not yet implemented");
}