var currentServiceId = null;
function createService(){
	$("#create_service_form_domain_id").val(currentDomainId);
	$("#create_service_form").dialog({"modal":true,"title":"Création d'un service"});
}
function doCreateService(){
	var name 		= $("#create_service_form_name").val();
	var code 		= $("#create_service_form_code").val();
	var domain_id 	= $("#create_service_form_domain_id").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/service.php",
		data	: {
			"code"		: code,
			"name"		: name,
			"domain_id"	: domain_id},
		dataType: "text",
		success	: function( data ) {
			$("#create_service_form").dialog("close");
			displayBusiness(currentDomainId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function displayService(serviceId){
	hideToolBox();
	$("#service_toolbox").show();
	if (serviceId == null){
		clearFrame();
		$("#service_create_component_button").button("disable");
		$("#service_create_instance_button").button("disable");
		currentServiceId = null;
	} else {
		changeImage("views/view_service.php?id="+serviceId);
		$("#service_create_component_button").button("enable");
		$("#service_create_instance_button").button("enable");
		currentServiceId = serviceId;
	}
}
function deleteService(id){
	if (!confirm("Etes-vous sûr de vouloir supprimer le service ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/service.php?id="+id,
		dataType: "text",
		success	: function(data) {
			displayBusiness(currentDomainId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function createServiceInstance(){
	$("#create_instance_form_service_id").val(currentServiceId);
	$("#create_instance_form").dialog({"modal":true,"title":"Création d'une instance"});
}
function doCreateServiceInstance(){
	var name = $("#create_instance_form_name").val();
	var serviceId = $("#create_instance_form_service_id").val();
	var environmentId = $("#create_instance_form_environment").val();
	if (environmentId == "NULL"){
		alert("Veuillez choisir un environnement");
		return;
	}
	$.ajax({
		type 	: "POST",
		url 	: "api/service_instance.php",
		data	: {
			"name"		 : name,
			"service_id" : serviceId,
			"environment_id" : environmentId},
		dataType: "text",
		success	: function( data ) {
			$("#create_instance_form").dialog("close");
			displayService(currentServiceId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function displayServiceInstance(id){
	
}
function deleteServiceInstance(id){
	if (!confirm("Etes-vous sûr de vouloir supprimer l'instance ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/service_instance.php?id="+id,
		dataType: "text",
		success	: function(data) {
			displayService(currentServiceId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}