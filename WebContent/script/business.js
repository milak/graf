var currentDomainId = null;
function displayBusiness(domainId){
	hideToolBox();
	$("#business_toolbox").show();
	if (domainId == null) {
		var id = $("#domainSelected").val();
		if (id != "null"){
			domainId = id;
		}
	}
	if (domainId != $("#domainSelected").val()){
		$("#domainSelected").val(""+domainId);
		$("#business_toolbox").controlgroup("refresh");
	}
	if (domainId == null) {
		$("#business_create_process_button").button("disable");
		$("#business_create_service_button").button("disable");
		$("#business_create_actor_button").button("disable");
		clearFrame();
		currentDomainId = null;
	} else {
		currentDomainId = domainId;
		$("#business_create_process_button").button("enable");
		$("#business_create_service_button").button("enable");
		$("#business_create_actor_button").button("enable");
		changeImage("views/view_business.php?id="+domainId);
	}
}
function recursiveRefreshStrategicAreaList(area){
	var html = "";
	if (area.areas.length == 0){
		html = '<option value="'+area.id+'">'+area.name+'</option>';
	} else {
		for (var i = 0; i < area.areas.length; i++){
			var subarea = area.areas[i];
			html += recursiveRefreshStrategicAreaList(subarea);
		}
	}
	return html;
}
function refreshStrategicAreaList(){
	$.getJSON( "api/view.php?view=strategic", function(result) {
		var areas = result.view.areas;
		$('#create_domain_form_area').html("");
		var options = "<option value='null'>~~Choisir une zone~~</option>";
		for (var i = 0; i < areas.length; i++){
			var area = areas[i];
			//options += '<option value="'+area.id+'">'+area.name+'</option>';
			options += recursiveRefreshStrategicAreaList(area);
		}
		$('#create_domain_form_area').append(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function createDomain(){
	refreshStrategicAreaList();
	$("#create_domain_form").dialog({"modal":true,"title":"Création d'un domaine","minWidth":500});
}
function doCreateDomain(){
	var name 	= $("#create_domain_form_name").val();
	var area_id 	= $("#create_domain_form_area").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/domain.php",
		data	: {
			"name"		: name,
			"area_id"	: area_id},
		dataType: "text",
		success	: function( data ) {
				refreshDomainList();
			displayStrategic();
			$("#create_domain_form").dialog("close");
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function searchDomain(){
	var id = $("#search_domain_form_list").val();
	if (id != "null"){
		displayBusiness(id);
	} else {
		displayBusiness(null);
	}
}
function deleteDomain(domainId){
	if (!confirm("Etes-vous sûr de vouloir supprimer le domaine ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/domain.php?id="+domainId,
		dataType: "text",
		success	: function(data) {
			displayStrategic();
			refreshDomainList();
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function refreshDomainList(){
	$.getJSON( "api/domain.php", function(result) {
		var domains = result.domains;
		var options = "<option value='null' selected>--choisir un domaine--</option>";
		for (var i = 0; i < domains.length; i++){
			var domain = domains[i];
			options += '<option value="'+domain.id+'">'+domain.name+'</option>';
		}
		$('#search_domain_form_list').html(options);
		$('#search_process_form_domain_list').html(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Impossible de charger les domaines</h1>"+textStatus+ " : " + error);
	});
}
function showDomainContext(id){
	$.getJSON( "api/domain.php?id="+id, function(domain_result) {
		var domain = domain_result.domains[0];
		var html = "<p>Domaine</p>";
		html +=  "<b>Nom</b> : "+domain.name + "<br/><br/>";
		$.getJSON( "api/process.php?domain_id="+id, function(result) {
			var processes = result.process;
			html += "<b>Processus</b> :";
			if (processes.length == 0){
				html += " aucun<br/>";
			} else {
				html += "<br/><ul>";
				for (var i = 0; i < processes.length; i++){
					var process = processes[i];
					html += "<li><a href=\"#\" onclick='hidePopup();displayProcess("+process.id+")'>"+process.name+"</a></li>";
				}
				html += "</ul>";
			}
			$.getJSON( "api/service.php?domain_id="+id, function(result) {
				var services = result.services;
				html += "<b>Services</b> :";
				if (services.length == 0){
					html += " aucun<br/>";
				} else {
					html += "<br/><ul>";
					for (var i = 0; i < services.length; i++){
						var service = services[i];
						html += "<li><a href=\"#\" onclick='hidePopup();displayService("+service.id+")'>"+service.name+"</a></li>";
					}
					html += "</ul>";
				}
				html += "<hr/>";
				html += " <button onclick='hidePopup();displayBusiness("+domain.id+")'><img src='images/63.png'/> ouvrir</button>";
				html += " <button onclick='hidePopup();deleteDomain("+domain.id+")'><img src='images/14.png'/> supprimer</button>";
				html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
				showPopup("Détail",html);
			});
		});
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}