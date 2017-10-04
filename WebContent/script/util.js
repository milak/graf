function changeImage(url){
	//$("#frame").attr("src",url);
	$("#frame").attr("data",url);
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
	$("#popup").dialog({"modal":true,"title":title,"minWidth":400});
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
			html +=  " <button onclick='hidePopup();displayProcess("+process.id+")'><img src='images/63.png'/> ouvrir</button>";
			html +=  " <button onclick='deleteProcess("+process.id+");hidePopup()'><img src='images/14.png'/> supprimer</button>";
			html +=  " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
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
				html += " <button onclick='hidePopup();displayBusiness("+domain.id+")'><img src='images/63.png'/> ouvrir</button>";
				html += " <button onclick='hidePopup();deleteDomain("+domain.id+")'><img src='images/14.png'/> supprimer</button>";
				html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
				showPopup("Détail",html);
			});
			}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
		});
	} else if (what == "step"){
		$.getJSON( "api/step.php?id="+id, function(result) {
			var step = result.steps[0];
			var html = "<p>Etape</p>";
			html += "<b>Nom</b> : "+step.name+"<br/><br/>";
			html += "<b>Type</b> : "+step.step_type_name+"<br/><br/>";
			html += "<hr/>";
			var sub_process_id = step.sub_process_id;
			if (sub_process_id != ""){
				html+="<button onclick='hidePopup();displayProcess("+sub_process_id+")'><img src='images/63.png'/> ouvrir</button>";
			}
			if (step.step_type_name != "START") {
				html += " <button>Supprimer</button>";
			}
			html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
			showPopup("Détail",html);
			}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
		});
	} else if (what == "box"){
		// nothing to do at now
	} else if (what == "instance"){
		$.getJSON( "api/service_instance.php?id="+id, function(result) {
			var instance = result.instances[0];
			var html = "<p>Instance</p>";
			html += "<b>Nom</b> : "+instance.name+"<br/><br/>";
			html += "<b>Environnement</b> : "+instance.environment.name+"<br/><br/>";
			html += "<hr/>";
			html+="<button onclick='hidePopup();displayServiceInstance("+id+")'><img src='images/63.png'/> ouvrir</button>";
			html += " <button onclick='hidePopup();deleteServiceInstance("+id+")'><img src='images/14.png'/> supprimer</button>";
			html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
			showPopup("Détail",html);
		}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
		});
	} else if (what == "actor"){
		$.getJSON( "api/actor.php?id="+id, function(result) {
			var actor = result.actors[0];
			var html = "<p>Acteur</p>";
			html += "<b>Nom</b> : "+actor.name+"<br/><br/>";
			html += "<hr/>";
			html += " <button onclick='hidePopup();deleteActor("+id+")'><img src='images/14.png'/> supprimer</button>";
			html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
			showPopup("Détail",html);
		}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
		});
	} else if (what == "service"){
		$.getJSON( "api/service.php?id="+id, function(result) {
			var service = result.services[0];
			var html = "<p>Service</p>";
			html += "<b>Nom</b> : "+service.name+"<br/><br/>";
			html += "<b>Code</b> : "+service.code+"<br/><br/>";
			html += "<hr/>";
			html += " <button onclick='hidePopup();displayService("+id+")'><img src='images/63.png'/> ouvrir</button>";
			html += " <button onclick='hidePopup();deleteService("+id+")'><img src='images/14.png'/> supprimer</button>";
			html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
			showPopup("Détail",html);
		}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
		});
	} else if (what == "component"){
		showComponentContext(id);
	} else {
		alert("An "+what+" of id "+id +" was clicked");
	}
}		