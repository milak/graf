function changeImage(url){
	$("#frame").attr("src",url);
}
function hideToolBox(){
	$("#default_toolbox").hide();
	$("#strategic_toolbox").hide();
	$("#business_toolbox").hide();
	$("#logic_toolbox").hide();
	$("#process_toolbox").hide();
	$("#technic_toolbox").hide();
	$("#views_toolbox").hide();
	$("#service_toolbox").hide();
}
function clearFrame(){
	changeImage("");
}
function showPopup(title,body){
	$("#popup").html(body);
	$("#popup").dialog({"modal":true,"title":title});
}
function hidePopup(){
	$("#popup").dialog("close");
}
function svgElementClicked(what,id){
	if (what == "process"){
		$.getJSON( "api/process.php?id="+id, function(result) {
			var process = result.process[0];
			var html = "<p>Processus</p>";
			html +=  " <b>Nom</b> : " +     process.name + "<br/><br/>";
			html +=  " <b>Domaine</b> : " + process.domain_name+"<br/><br/>";
			html +=  " <hr/>";
			html +=  " <button onclick='hidePopup();displayProcess("+process.id+")'>Ouvrir</button>";
			html +=  " <button onclick='deleteProcess("+process.id+");hidePopup()'>Supprimer</button>";
			html +=  " <button onclick='hidePopup()'>Fermer</button>";
			showPopup("Détail",html);
			}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
		});
	} else if (what == "domain"){
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
						process = processes[i];
						html += "<li><a href=\"#\" onclick='hidePopup();displayProcess("+process.id+")'>"+process.name+"</a></li>";
					}
					html += "</ul>";
				}
				html += "<hr/>";
				html += " <button onclick='hidePopup();displayBusiness("+domain.id+")'>Ouvrir</button>";
				html += " <button onclick='hidePopup();deleteDomain("+domain.id+")'>Supprimer</button>";
				html += " <button onclick='hidePopup()'>Fermer</button>";
				showPopup("Détail",html);
			});
			}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
		});
	} else if (what == "step"){
		$.getJSON( "api/step.php?id="+id, function(result) {
			var step = result.steps[0];
			var html = "<p>Etape</p>";
			html += "<b>Nom</b> : "+step.name+"<br/>";
			html += "<b>Type</b> : "+step.step_type_name;
			html += "<hr/>";
			var sub_process_id = step.sub_process_id;
			if (sub_process_id != ""){
				html+="<button onclick='hidePopup();displayProcess("+sub_process_id+")'>Ouvrir</button>";
			}
			if (step.step_type_name != "START") {
				html += " <button>Supprimer</button>";
			}
			html += " <button onclick='hidePopup()'>Fermer</button>";
			showPopup("Détail",html);
			}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
		});
	} else if (what == "box"){
		// nothing to do at now
	} else if (what == "instance"){
		var html = "<p>Instance</p>";
		html += "<b>Nom</b> : <br/>";
		html += "<b>Environnement</b> : ";
		html += "<hr/>";
		html+="<button onclick='hidePopup();displayServiceInstance("+id+")'>Ouvrir</button>";
		html += " <button onclick='hidePopup();deleteServiceInstance("+id+")'>Supprimer</button>";
		html += " <button onclick='hidePopup()'>Fermer</button>";
		showPopup("Détail",html);
	} else if (what == "service"){
		var html = "<p>Service</p>";
		html += "<b>Nom</b> : <br/>";
		html += "<b>Code</b> : ";
		html += "<hr/>";
		html+="<button onclick='hidePopup();displayService("+id+")'>Ouvrir</button>";
		html += " <button onclick='hidePopup();deleteService("+id+")'>Supprimer</button>";
		html += " <button onclick='hidePopup()'>Fermer</button>";
		showPopup("Détail",html);
	} else {
		alert("An "+what+" of id "+id +" was clicked");
	}
}		