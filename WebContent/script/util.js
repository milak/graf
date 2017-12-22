var panZoomInstance = null;
function changeImage(url){
	if (panZoomInstance != null){
		panZoomInstance.destroy();
		panZoomInstance = null;
	}
	if (url == null){
		$('#frame').html("");
		return;
	}
	$.get( url, function( data ) {
		$('#frame').html(data);
		panZoomInstance = svgPanZoom("#frame", {
		    zoomEnabled				: true,
		    dblClickZoomEnabled		: false,
		    controlIconsEnabled		: true,
		    fit						: true,
		    center					: false,
		    minZoom					: 0.1,
		    zoomScaleSensitivity 	: 0.3
		});
	});
}
function hideToolBox(){
	$("#default_toolbox"  ).hide();
	$("#strategic_toolbox").hide();
	$("#business_toolbox" ).hide();
	$("#logic_toolbox"    ).hide();
	$("#process_toolbox"  ).hide();
	$("#technic_toolbox"  ).hide();
	$("#views_toolbox"    ).hide();
	$("#service_toolbox"  ).hide();
}
function clearFrame(){
	changeImage(null);
}
function showPopup(title,body){
	$("#popup").html(body);
	$("#popup").dialog({"modal":true,"title":title,"minWidth":400});
}
function hidePopup(){
	$("#popup").dialog("close");
}
function svgElementDblClicked(what,id){
	alert("svgElementDblClicked()");
}
function svgElementClicked(what,id){
	if (what == "process"){
		showProcessContext(id);
	} else if (what == "domain"){
		showDomainContext(id);
	} else if (what == "step"){
		showProcessStepContext(id);
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
		$.getJSON( "api/element.php?id="+id, function(result) {
			var element = result.elements[0];
			var html = "<p>Acteur</p>";
			html += "<b>Nom</b> : "+element.name+"<br/><br/>";
			html += "<b>Classe</b> : "+element.class.name+"<br/><br/>";
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
function sortByName(a, b){
	return a.name.localeCompare(b.name);
}