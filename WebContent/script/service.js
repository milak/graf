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
		$("#service_create_software_button").button("disable");
		$("#service_create_device_button").button("disable");
		$("#service_create_service_button").button("disable");
		$("#service_create_data_button").button("disable");
		$("#service_create_instance_button").button("disable");
		currentServiceId = null;
	} else {
		changeImage("views/view_service.php?id="+serviceId);
		$("#service_create_software_button").button("enable");
		$("#service_create_device_button").button("enable");
		$("#service_create_service_button").button("enable");
		$("#service_create_data_button").button("enable");
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
function createComponent(type){
	$("#create_component_form_type").val(type);
	$("#create_component_form_device").hide();
	$("#create_component_form_software").hide();
	$("#create_component_form_data").hide();
	$("#create_component_form_service").hide();
	if (type == "device") {
		$.getJSON( "api/device.php", function(result) {
			var devices = result.devices;
			var html = "<option value='NULL'>~~sélectionner un matériel~~</option>";
			for (var i = 0; i < devices.length; i++){
				html += "<option value='"+devices[i].id+"'>"+devices[i].name+"</option>";
			}
			$("#create_component_form_device").html(html);
			$("#create_component_form_device").show();
			$("#create_component_form").dialog({"modal":true,"title":"Ajout d'un matériel"});
		}).fail(function(jxqr,textStatus,error){
			alert(textStatus+" : "+error);
		});
	} else if (type == "software") {
		$.getJSON( "api/software.php", function(result) {
			var softwares = result.softwares;
			var html = "<option value='NULL'>~~sélectionner un logiciel~~</option>";
			for (var i = 0; i < softwares.length; i++){
				html += "<option value='"+softwares[i].id+"'>"+softwares[i].name+"</option>";
			}
			$("#create_component_form_software").html(html);
			$("#create_component_form_software").show();
			$("#create_component_form").dialog({"modal":true,"title":"Ajout d'un logiciel"});
		}).fail(function(jxqr,textStatus,error){
			alert(textStatus+" : "+error);
		});
	} else if (type == "data") {
		$.getJSON( "api/data.php", function(result) {
			var data = result.data;
			var html = "<option value='NULL'>~~sélectionner une donnée~~</option>";
			for (var i = 0; i < data.length; i++){
				html += "<option value='"+data[i].id+"'>"+data[i].name+"</option>";
			}
			$("#create_component_form_data").html(html);
			$("#create_component_form_data").show();
			$("#create_component_form").dialog({"modal":true,"title":"Ajout d'une donnée"});
		}).fail(function(jxqr,textStatus,error){
			alert(textStatus+" : "+error);
		});
	} else if (type == "service") {
		$.getJSON( "api/service.php", function(result) {
			var services = result.services;
			var html = "<option value='NULL'>~~sélectionner un service~~</option>";
			for (var i = 0; i < services.length; i++){
				html += "<option value='"+services[i].id+"'>"+services[i].name+"</option>";
			}
			$("#create_component_form_service").html(html);
			$("#create_component_form_service").show();
			$("#create_component_form").dialog({"modal":true,"title":"Ajout d'un logiciel"});
		}).fail(function(jxqr,textStatus,error){
			alert(textStatus+" : "+error);
		});
	}
}
function doCreateComponent(){
	var type = $("#create_component_form_type").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/component.php",
		data	: {
			"service"		: currentServiceId,
			"data_id"		: $("#create_component_form_data").val(),
			"device_id"		: $("#create_component_form_device").val(),
			"software_id"	: $("#create_component_form_software").val(),
			"service_id"	: $("#create_component_form_service").val()
		},
		dataType: "text",
		success	: function( data ) {
			$("#create_instance_form").dialog("close");
			displayService(currentServiceId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}