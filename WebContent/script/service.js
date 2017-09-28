function createService(domainId){
	$("#create_service_form_domain_id").val(domainId);
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
			"domain_id"	: area_id},
		dataType: "text",
		success	: function( data ) {
			$("#create_service_form").dialog("close");
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
		//$("#process_create_step_button").button("disable");
	} else {
		changeImage("views/view_service.php?id="+serviceId);
		//$("#process_create_step_button").button("enable");
	}
}