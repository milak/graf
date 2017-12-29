var currentServiceId = null;
var currentComponentId = null;
function refreshServiceList(){
	$.getJSON("api/service.php", function(result){
		var services = result.services;
		var options = "<option value='null' selected>--choisir un service--</option>";
		for (var i = 0; i < services.length; i++){
			var service = services[i];
			options += '<option value="'+service.id+'">'+service.name+'</option>';
		}
		$('#search_service_form_list').html(options);
		$('#edit_component_form_service_list').html(options);
		$('#process_step_create_form_service_list').html(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Impossible de charger les services</h1>"+textStatus+ " : " + error);
	});
}
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
			refreshServiceList();
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
	$("#create_instance_form").dialog({"modal":true,"title":"Création d'une instance","minWidth":500});
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
			refreshServiceList();
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
function subCreateComponent(type,aServices,aSoftwares,aDevices,aDatas){
	$("#create_component_form_type"    ).val(type);
	$("#create_component_form_device"  ).hide();
	$("#create_component_form_software").hide();
	$("#create_component_form_data"    ).hide();
	$("#create_component_form_service" ).hide();
	if (type == "device") {
		$.getJSON( "api/device.php", function(result) {
			var devices = result.devices;
			var html = "<option value='NULL'>~~sélectionner un matériel~~</option>";
			for (var i = 0; i < devices.length; i++){
				if (aDevices[devices[i].id] == null){
					html += "<option value='"+devices[i].id+"'>"+devices[i].name+"</option>";
				}
			}
			$("#create_component_form_device_list").html(html);
			$("#create_component_form_device").show();
			$("#create_component_form").dialog({"modal":true,"title":"Ajout d'un matériel","minWidth":600});
		}).fail(function(jxqr,textStatus,error){
			alert(textStatus+" : "+error);
		});
	} else if (type == "software") {
		$.getJSON( "api/software.php", function(result) {
			var softwares = result.softwares;
			var html = "<option value='NULL'>~~sélectionner un logiciel~~</option>";
			for (var i = 0; i < softwares.length; i++){
				if (aSoftwares[softwares[i].id] == null){
					html += "<option value='"+softwares[i].id+"'>"+softwares[i].name+"</option>";
				}
			}
			$("#create_component_form_software_list").html(html);
			$("#create_component_form_software").show();
			$("#create_component_form").dialog({"modal":true,"title":"Ajout d'un logiciel","minWidth":600});
		}).fail(function(jxqr,textStatus,error){
			alert(textStatus+" : "+error);
		});
	} else if (type == "data") {
		$.getJSON( "api/data.php", function(result) {
			var data = result.data;
			var html = "<option value='NULL'>~~sélectionner une donnée~~</option>";
			for (var i = 0; i < data.length; i++){
				if (aDatas[data[i].id] == null){
					html += "<option value='"+data[i].id+"'>"+data[i].name+"</option>";
				}
			}
			$("#create_component_form_data_list").html(html);
			$("#create_component_form_data").show();
			$("#create_component_form").dialog({"modal":true,"title":"Ajout d'une donnée","minWidth":600});
		}).fail(function(jxqr,textStatus,error){
			alert(textStatus+" : "+error);
		});
	} else if (type == "service") {
		$.getJSON( "api/service.php", function(result) {
			var services = result.services;
			var html = "<option value='NULL'>~~sélectionner un service~~</option>";
			for (var i = 0; i < services.length; i++){
				if (aServices[services[i].id] == null){
					html += "<option value='"+services[i].id+"'>"+services[i].name+"</option>";
				}
			}
			$("#create_component_form_service_list").html(html);
			$("#create_component_form_service").show();
			$("#create_component_form").dialog({"modal":true,"title":"Ajout d'un service","minWidth":600});
		}).fail(function(jxqr,textStatus,error){
			alert(textStatus+" : "+error);
		});
	}
}
function createComponent(type){
	$.getJSON( "api/component.php?service="+currentServiceId, function(result) {
		var components = result.components;
		var services 	= new Array();
		var softwares 	= new Array();
		var devices 	= new Array();
		var datas 		= new Array();
		for (var i = 0; i < components.length; i++){
			var component = components[i];
			if (component.type == "service"){
				services[component.service_id] = component;
			} else if (component.type == "device"){
				devices[component.device_id] = component;
			} else if (component.type == "software"){
				softwares[component.software_id] = component;
			} else if (component.type == "data"){
				datas[component.data_id] = component;
			}
		}
		subCreateComponent(type,services,softwares,devices,datas);
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function doCreateComponent(){
	var type = $("#create_component_form_type").val();
	var area_id = $("#create_component_form_area").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/component.php",
		data	: {
			"type"			: type,
			"area_id"		: area_id,
			"service"		: currentServiceId,
			"data_id"		: $("#create_component_form_data_list").val(),
			"device_id"		: $("#create_component_form_device_list").val(),
			"software_id"	: $("#create_component_form_software_list").val(),
			"service_id"	: $("#create_component_form_service_list").val()
		},
		dataType: "text",
		success	: function( data ) {
			$("#create_component_form").dialog("close");
			displayService(currentServiceId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function displayComponent(componentId){
	$.getJSON( "api/component.php?id="+componentId, function(result) {
		var component = result.components[0];
		if (component.type == "service"){
			displayService(component.service_id);
		}
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function onEditComponentFormAreaChanged(){
	var area_id = $("#edit_component_form_area").val();
	var orig_area_id = $("#edit_component_form_area_orig_value").val();
	if (area_id != orig_area_id){
		$("#edit_component_form_valider").prop("disabled",false);
	} else {
		$("#edit_component_form_valider").prop("disabled",true);
	}
}
function updateComponent(){
	var area_id = $("#edit_component_form_area").val();
	var component_id = $("#edit_component_form_component_id").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/component.php",
		dataType: "text",
		data	: {
			id 		: component_id,
			area_id : area_id
		},
		success	: function(data) {
			displayService(currentServiceId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function unlinkComponent(componentId){
	$.ajax({
		type 	: "DELETE",
		url 	: "api/component.php?id="+componentId,
		dataType: "text",
		success	: function(data) {
			displayService(currentServiceId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function refreshComponentContext(componentId){
	$("#edit_component_form_valider").prop("disabled",true);
	$("#edit_component_form_service_list").val(currentServiceId);
	$.getJSON( "api/component.php?id="+componentId, function(result) {
		var component = result.components[0];
		if (component.type == "service"){
			$("#edit_component_form_ouvrir").show();
		} else {
			$("#edit_component_form_ouvrir").hide();
		}
		$("#edit_component_form_name").val(component.name);
		$("#edit_component_form_area").val(component.area_id);
		$("#edit_component_form_area_orig_value").val(component.area_id);
		$("#edit_component_form_component_id").val(component.id);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
	$.getJSON( "api/component.php?service="+currentServiceId, function(result) {
		var componentList = result.components;
		componentList.sort(sortByName);
		var componentsById = new Array();
		for (var i = 0; i < componentList.length; i++){
			var component = componentList[i];
			component.linked = false;
			componentsById[component.id] = component;
		}
		$.getJSON( "api/component_link.php?from_component_id="+componentId, function(result) {
			var links = result.links;
			var html = "";
			for (var i = 0; i < links.length; i++){
				var link = links[i];
				var to_component = componentsById[link.to_component_id];
				if (to_component == null){
					alert("TODO charger les composants externes");
					to_component = {"name" : "TODO : externe"};
				}
				to_component.linked = true;
				html += "<tr><td>"+to_component.name+"</td><td>"+link.protocole+"</td><td>"+link.port+"</td><td><a href='#' onclick='removeComponentLink("+componentId+","+link.to_component_id+")'>supprimer</a></td></tr>";
			}
			$("#edit_component_form_links").html(html);
			html = "";
			for (var i = 0; i < componentList.length; i++){
				var component = componentList[i];
				if (component.id == componentId){
					continue;
				}
				if (!component.linked){
					html += "<option value='"+component.id+"'>"+component.name+"</option>";
				}
			}
			$("#edit_component_form_component_list").html(html);
		}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
		});
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function filterComponentList(serviceId){
	$.getJSON( "api/component.php?service="+serviceId, function(result) {
		var componentList = result.components;
		componentList.sort(sortByName);
		html = "";
		for (var i = 0; i < componentList.length; i++){
			var component = componentList[i];
			if (component.id == currentComponentId){
				continue;
			}
			if (!component.linked){
				html += "<option value='"+component.id+"'>"+component.name+"</option>";
			}
		}
		$("#edit_component_form_component_list").html(html);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function showComponentContext(componentId){
	hideAddComponentLinkForm();
	$("#edit_component_form_component_list").html("");
	$("#edit_component_form_links").html("");
	$("#edit_component_form_ouvrir").hide();
	currentComponentId = componentId;
	refreshComponentContext(componentId);
	$("#edit_component_form").dialog({"modal":true,"title":"Détail du composant","minWidth":500});
}
function hideAddComponentLinkForm(){
	$('#edit_component_form_toggle2').hide();
	$('#edit_component_form_toggle1').show();
}
function removeComponentLink(from_component_id,to_component_id){
	$.ajax({
		type 	: "DELETE",
		url 	: "api/component_link.php?from_component_id="+from_component_id+"&to_component_id="+to_component_id,
		dataType: "text",
		success	: function( data ) {
			refreshComponentContext(currentComponentId);
			displayService(currentServiceId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function addComponentLink(){
	var protocole = $("#edit_component_form_protocole").val();
	var port = $("#edit_component_form_port").val();
	var to_component_id = $("#edit_component_form_component_list").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/component_link.php",
		data	: {
			"protocole"			: protocole,
			"port"				: port,
			"from_component_id" : currentComponentId,
			"to_component_id" 	: to_component_id},
		dataType: "text",
		success	: function( data ) {
			refreshComponentContext(currentComponentId);
			displayService(currentServiceId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function searchService(){
	var selectedService = $("#search_service_form_list").val();
	if (selectedService == "NULL"){
		return;
	}
	displayService(selectedService);
}