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
	try{
		$("#process_script_editor_form").dialog("close");
	} catch(e){} // ignorer les erreurs de non initialisation de la fenetre
	try{
		$("#solution_script_editor_form").dialog("close");
	} catch(e){} // ignorer les erreurs de non initialisation de la fenetre
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
function deleteToscaItem(id){
	if (confirm("Etes-vous sur de vouloir supprimer le noeud "+id)){
		var tosca = $("#solution_script_editor_form_text").val();
		tosca = jsyaml.load(tosca);
		var topology_template = tosca.topology_template;
		var node_templates = topology_template.node_templates;
		delete node_templates[id];
		var text = jsyaml.safeDump(tosca);
		$("#solution_script_editor_form_text").val(text.trim());
		saveSolutionScript(currentSolutionId);
	}
}
function showToscaItemContext(toscaItemId){
	var tosca = $("#solution_script_editor_form_text").val();
	tosca = jsyaml.load(tosca);
	var html = "<p>Element</p>";
	html += "<b>Nom</b> : "+toscaItemId+"<br/><br/>";
	var topology_template = tosca.topology_template;
	var node_templates = topology_template.node_templates;
	var node = node_templates[toscaItemId];
	html += "<b>Type</b> : " + node.type+"<br/><br/>";
	var id = null;
	if (typeof node.properties != 'undefined'){
		html += "<b>Propriétés : </b>";
		html += "<ul>";
		for (var i = 0; i < node.properties.length; i++){
			$.each(node.properties[i], function(index, value) {
				if (index == "id"){
					id = value;
				}
				html += "<li>"+index+" = "+value +"</li>";
			});
		}
		html += "</ul>";
	}
	/*$.each(node_templates, function(index, value) {
		html += index+" -> " + value+"<hr/>";
	});*/ 
	/*for (var i = 0; i < node_templates.length; i++){
		html += node_templates[i]+"<hr/>";
	}*/
	//html += "Description "+tosca.description;
	html += "<hr/>";
	if (id != null){
		$.getJSON( "api/element.php?id="+id, function(result) {
			if (result.elements.length != 0){
				var element = result.elements[0];
				html += "Class : " + element.class.name + "<br/>";
				html += "Category : " + element.category.name + "<br/>";
			}
			html += "<button onclick='hidePopup();displayServiceInstance("+id+")'><img src='images/63.png'/> ouvrir</button>";
			html += " <button onclick='hidePopup();deleteToscaItem(\""+toscaItemId+"\")'><img src='images/14.png'/> supprimer</button>";
			html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
			showPopup("Détail",html);
		});
	} else {
		html += " <button onclick='hidePopup();deleteToscaItem(\""+toscaItemId+"\")'><img src='images/14.png'/> supprimer</button>";
		html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
		showPopup("Détail",html);
	}
	// Pour sérialiser
	// var text = YAML.stringify(tosca);
	// $("#solution_script_editor_form_text").val(text);
	//html += " <button onclick='hidePopup();deleteServiceInstance("+id+")'><img src='images/14.png'/> supprimer</button>";
}
function svgElementClicked(what,id){
	if (what == "process"){
		showProcessContext(id);
	} else if (what == "domain"){
		showDomainContext(id);
	} else if (what == "step"){
		showProcessStepContext(id);
	} else if (what == "tosca_item"){
		showToscaItemContext(id);
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
	} else if (what == "data"){
		$.getJSON( "api/element.php?id="+id, function(result) {
			var element = result.elements[0];
			var html = "<p>Donnée</p>";
			html += "<b>Nom</b> : "+element.name+"<br/><br/>";
			html += "<b>Classe</b> : "+element.class.name+"<br/><br/>";
			html += "<hr/>";
			html += " <button onclick='hidePopup();deleteElement("+id+")'><img src='images/14.png'/> supprimer</button>";
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
	} else if (what == "solution"){
		showSolutionContext(id);
	} else if (what == "component"){
		showComponentContext(id);
	} else {
		alert("An "+what+" of id "+id +" was clicked");
	}
}
function onSearchElementFormCategoryChange(){
	var categoryName = $("#search_element_form_category").val();
	$.getJSON( "api/element_class.php", function(result) {
		var categories = result.categories;
		var htmlClasses = "<option value='NULL'>~~Sélectionner une classe~~</option>";
		for (var i = 0; i < categories.length; i++){
			var category = categories[i];
			if (categoryName != "NULL"){
				if (categoryName != category.name){
					continue;
				}
			}
			for (var j = 0; j < category.classes.length; j++){
				var classe = category.classes[j];
				htmlClasses += "<option value='"+classe.id+"'>"+classe.name+"</option>";
			}
		}
		$("#search_element_form_class").html(htmlClasses);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
var datatableElements = null;
function getToscaNodeTypeFromCategory(itemCategory){
	switch (itemCategory) {
		case "actor" :
			return "tosca.nodes.Root";
		case "data" :
			return "tosca.nodes.Database";
		case "device" :
			return "tosca.nodes.Root";
		case "process" :
			return "tosca.nodes.Compute";
		case "server" :
			return "tosca.nodes.Compute";
		case "service" :
			return "tosca.nodes.Compute";
		case "software" :	
			return "tosca.nodes.SoftwareComponent";
		case "solution" :	
			return "tosca.nodes.Compute";
		default : 
			return "tosca.nodes.Root";
	}
}
function addElementInTosca(id,name,category){
	var tosca = $("#solution_script_editor_form_text").val();
	tosca = jsyaml.load(tosca);
	var topology_template = tosca.topology_template;
	var node_templates = topology_template.node_templates;
	var index = 1;
	var nodeName = name;
	// Rechercher si l'élément n'a pas déjà été ajouté
	while (true) {
		var found = false;
		for (var node in node_templates){
			if (node == nodeName){
				found = true;
				break;
			}
		}
		if (!found) {
			break;
		}
		// Si trouvé, ajouter un chiffre et chercher à nouveau
		nodeName = name + "_" + index;
		index++;
	}
	// Ajouter le node
	var properties = new Array();
	properties["id"] = id;
	var node = { "type" : getToscaNodeTypeFromCategory(category), "name" : nodeName, properties : properties};
	node_templates[nodeName] = node;
	var text = jsyaml.safeDump(tosca);
	$("#solution_script_editor_form_text").val(text.trim());
	saveSolutionScript(currentSolutionId);
}
function onSearchElementFormSearchClick(){
	if (datatableElements == null){
		datatableElements = $("#search_element_form_result").dataTable(/* {
	        "columnDefs": [ {
	            "targets": -1,
	            "data": null,
	            "defaultContent": "<button>Add</button>"
	        } ]
	    }*/);
	}
	var url = "api/element.php";
	var selectClass = $("#search_element_form_class").val();
	if (selectClass != "NULL"){
		url += "?class_name="+selectClass;
	} else {
		var selectCategorie = $("#search_element_form_category").val();
		if (selectCategorie != "NULL"){
			url += "?category_name="+selectCategorie;
		}
	}
	datatableElements.fnClearTable();
	$.getJSON( url, function(result) {
		var selectName = $("#search_element_form_name").val().trim();
		var html = "";
		var elements = result.elements;
		var data = new Array();
		for (var i = 0; i < elements.length; i++){
			var element = elements[i];
			if (selectName != ""){
				if (element.name != selectName){
					continue;
				}
			}
			var row = new Array();
			row.push(element.id);
			row.push(element.name);
			row.push(element.class.name);
			row.push(element.category.name);
			var usableName = element.name.replace(new RegExp('[^a-zA-Z]','g'),'_');
			row.push("<button onClick='event.preventDefault();addElementInTosca(\""+element.id+"\",\""+usableName+"\",\""+element.category.name+"\");'>Add</button>");
			data.push(row);
			//html += "<tr><td></td><td>"+element.name+"</td><td>"+element.class.name+"</td><td>"+element.category.name+"</td></tr>";
		}
		if (data.length > 0){
			datatableElements.fnAddData(data);
		}
		//$("#search_element_form_result_body").html(html);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function openSearchElementForm(){
	$.getJSON( "api/element_class.php", function(result) {
		var categories = result.categories;
		var htmlClasses = "<option value='NULL'>~~Sélectionner une classe~~</option>";
		var html = "<option value='NULL'>~~Sélectionner une catégorie~~</option>";
		for (var i = 0; i < categories.length; i++){
			var category = categories[i];
			html += "<option value='"+category.id+"'>"+category.name+"</option>";
			for (var j = 0; j < category.classes.length; j++){
				var classe = category.classes[j];
				htmlClasses += "<option value='"+classe.id+"'>"+classe.name+"</option>";
			}
		}
		$("#search_element_form_category").html(html);
		$("#search_element_form_class").html(htmlClasses);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
	$("#search_element_form").dialog({"modal":true,"title":"Chercher un élément","minWidth":1100,"minHeight":800});
}
function sortByName(a, b){
	return a.name.localeCompare(b.name);
}