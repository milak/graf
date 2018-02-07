function displaySolution(solutionId){
	if ($("#logic_toolbox").is(":hidden")){
		hideToolBox();
		$("#logic_toolbox").show();
	}
	if (solutionId == null){
		currentItem = null;
		clearFrame();
		$("#logic_import_item_button").		button("disable");
		$("#logic_create_instance_button").	button("disable");
		$("#logic_edit_button").			button("disable");
	} else {
		currentItem = {
			'id' 	: solutionId,
			refresh : function(){
				changeImage("views/view_logique.php?id="+this.id);
				$("#logic_import_item_button").		button("enable");
				$("#logic_create_instance_button").	button("enable");
				$("#logic_edit_button").			button("enable");
				loadSolutionScript(this.id);
				return this;
			},
			addItem : function(itemId){
				$.getJSON("api/element.php?id="+itemId, function(result) {
					var element = result.elements[0];
					var name = element.name.replace(new RegExp('[^a-zA-Z0-9]','g'),'_');
					var area = $("#search_item_form_area").val();
					if (area == "null"){
						alert("Vous devez fournir une zone");
						return;
					}
					var tosca = $("#solution_script_editor_form_text").val();
					tosca = jsyaml.load(tosca);
					var topology_template = tosca.topology_template;
					var node_templates = topology_template.node_templates;
					if (node_templates == null){
						topology_template.node_templates = {};
						node_templates = topology_template.node_templates;
					}
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
					properties.push({"id" : itemId});
					properties.push({"area" : area});
					var node = { "type" : getToscaNodeTypeFromCategory(element.category.name), "name" : nodeName, properties : properties};
					node_templates[nodeName] = node;
					var text = jsyaml.safeDump(tosca);
					$("#solution_script_editor_form_text").val(text.trim());
					$.ajax({
						type 	: "POST",
						url 	: "api/element.php",
						data	: {
							"id"		: currentItem.id,
							"child_id"	: itemId
						},
						dataType: "text",
						success	: function(data) {
							saveSolutionScript(currentItem.id);
						}
					}).fail(function(jxqr,textStatus,error){
						alert(textStatus+" : "+error);
					});
				}).fail(function(jxqr,textStatus,error) {
					showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
				});
			}
		}.refresh();
	}
}
function loadSolutionScript(solutionId){
	$.ajax({
		type : "GET",
		url  : "api/element.php?document=true&type=TOSCA&id="+solutionId,
		dataType : "text",
		success : function (data){
			$("#solution_script_editor_form_text").val(data);
		}
	}).fail(function(jxqr,textStatus,error){
		$("#solution_script_editor_form_text").val("tosca_definitions_version: tosca_simple_yaml_1_0\ndescription: template.\ntopology_template:\n  inputs:\n  node_templates:\n    SampleNode:\n      type: tosca.nodes.Compute\n");
	});
}
function saveSolutionScript(itemId){
	var script = $("#solution_script_editor_form_text").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/element.php",
		data	: {
			"id"		: itemId,
			"document"	: script,
			"type"		: "TOSCA"
		},
		dataType: "text",
		success	: function( data ) {
			displaySolution(itemId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function searchSolution(){
	$.getJSON("api/element.php?category_name=solution", function(result){
		var elements = result.elements;
		var options = "<option value='null' selected>--choisir une solution--</option>";
		for (var i = 0; i < elements.length; i++){
			var element = elements[i];
			options += '<option value="'+element.id+'">'+element.name+'</option>';
		}
		$('#search_solution_form_list').html(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Impossible de charger les solutions</h1>"+textStatus+ " : " + error);
	});
	$("#search_solution_form").dialog({"modal":true,"title":"Chercher une solution","minWidth":500})
}
function solutionSelected(){
	var selectedSolution = $("#search_solution_form_list").val();
	if (selectedSolution == "null"){
		return;
	}
	displaySolution(selectedSolution);
}
function showSolutionContext(id){
	$.getJSON( "api/element.php?id="+id, function(result) {
		var element = result.elements[0];
		var html = "<p>Solution</p>";
		html += "<b>Nom</b> : "+element.name+"<br/><br/>";
		html += "<hr/>";
		html += " <button onclick='hidePopup();displaySolution(\""+id+"\")'><img src='images/63.png'/> ouvrir</button>";
		html += " <button onclick='hidePopup();deleteElement(\""+id+"\")'><img src='images/14.png'/> supprimer</button>";
		html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
		showPopup("Détail",html);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function editSolutionScript(){
	$("#solution_script_editor_form").dialog({"modal":false,"title":"Edition de la solution","minWidth":500,"minHeight":500});
	try{
		$("#solution_script_editor_form").dialog("update");
	}catch(exception){}
}
function importItemInSolution(){
	openImportItemForm(currentItem.addItem);
}
// Tosca functions
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
function deleteToscaItem(id){
	if (confirm("Etes-vous sur de vouloir supprimer le noeud "+id+" ?")){
		var tosca = $("#solution_script_editor_form_text").val();
		tosca = jsyaml.load(tosca);
		var topology_template = tosca.topology_template;
		var node_templates = topology_template.node_templates;
		delete node_templates[id];
		var text = jsyaml.safeDump(tosca);
		$("#solution_script_editor_form_text").val(text.trim());
		saveSolutionScript(currentItem.id);
	}
}