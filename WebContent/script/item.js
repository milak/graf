var datatableItems = null;
var itemClasses = null;
var functionToCallWhenAddClicked = null;
function openSearchItemForm(aFunctionToCallWhenAddClicked){
	functionToCallWhenAddClicked = aFunctionToCallWhenAddClicked;
	$.getJSON( "api/element_class.php", function(result) {
		itemClasses = new Array();
		var categories = result.categories;
		var htmlClasses = "<option value='NULL'>~~Sélectionner une classe~~</option>";
		var html = "<option value='NULL'>~~Sélectionner une catégorie~~</option>";
		for (var i = 0; i < categories.length; i++){
			var category = categories[i];
			html += "<option value='"+category.id+"'>"+category.name+"</option>";
			for (var j = 0; j < category.classes.length; j++){
				var classe = category.classes[j];
				htmlClasses += "<option value='"+classe.id+"'>"+classe.name+"</option>";
				itemClasses[classe.id] = classe;
			}
		}
		$("#search_item_form_category").html(html);
		$("#search_item_form_class").html(htmlClasses);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
	$("#search_item_form").dialog({"modal":true,"title":"Chercher un élément","minWidth":1100,"minHeight":800});
}
function onSearchItemFormClassChange(){
	var classId = $("#search_item_form_class").val();
	var enable = true;
	if (classId == "NULL"){
		enable = false;
	} else {
		classe = itemClasses[classId];
		if (classe.abstract == "true"){
			enable = false;
		}
	}
	$("#search_item_form_create_button").prop("disabled",!enable);
}
function onSearchItemFormCategoryChange(){
	var categoryName = $("#search_item_form_category").val();
	// TODO optimiser soit en gardant en cache soit en ne cherchant que les classes nécessaires
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
		$("#search_item_form_class").html(htmlClasses);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function onSearchItemFormCreateClick(){
	var name 		= $("#search_item_form_name").val();
	if (name == ""){
		alert("Vous devez saisir un nom");
		return;
	}
	var className 	= $("#search_item_form_class").val();
	if (className == "null"){
		alert("Vous devez choisir une classe");
		return;
	}
	$.ajax({
		type 	: "POST",
		url 	: "api/element.php",
		data	: {
			"name"		: name,
			"class_name": className
		},
		dataType: "text",
		success	: function( data ) {
			onSearchItemFormSearchClick();
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function removeItem(parentId,childId){
	if (!confirm("Etes-vous sûr de vouloir retirer cet élément ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/element.php?id="+parentId+"&child_id="+childId,
		dataType: "text",
		success	: function(data) {
			displayBusiness(currentItemId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function deleteItem(itemId){
	if (!confirm("Etes-vous sûr de vouloir supprimer cet élément ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/element.php?id="+itemId,
		dataType: "text",
		success	: function(data) {
			displayBusiness(currentItemId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function onSearchItemFormDeleteClick(itemId){
	if (confirm("Etes-vous sûr de vouloir supprimer l'élément de la base ?")){
		$.ajax({
			type 	: "DELETE",
			url 	: "api/element.php?id="+itemId,
			dataType: "text",
			success	: function( data ) {
				onSearchItemFormSearchClick();
			}
		}).fail(function(jxqr,textStatus,error){
			alert(textStatus+" : "+error);
		});
	}
}
function onSearchItemFormSearchClick(){
	if (datatableItems == null){
		datatableItems = $("#search_item_form_result").dataTable();
	}
	var url = "api/element.php";
	var selectClass = $("#search_item_form_class").val();
	if (selectClass != "NULL"){
		url += "?class_name="+selectClass;
	} else {
		var selectCategorie = $("#search_item_form_category").val();
		if (selectCategorie != "NULL"){
			url += "?category_name="+selectCategorie;
		}
	}
	datatableItems.fnClearTable();
	$.getJSON( url, function(result) {
		var selectName = $("#search_item_form_name").val().trim();
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
			row.push("<p title='"+element.id+"'>"+element.name+"</p>");
			row.push(element.class.name);
			row.push(element.category.name);
			var label = "<button onClick='event.preventDefault();onSearchItemFormAddClick(\""+element.id+"\");'>Ajouter</button>";
			label    += "<button onClick='event.preventDefault();onSearchItemFormDeleteClick(\""+element.id+"\");'>Effacer</button>";
			row.push(label);
			data.push(row);
		}
		if (data.length > 0){
			datatableItems.fnAddData(data);
		}
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function onSearchItemFormAddClick(id){
	functionToCallWhenAddClicked(id);
}
function showToscaItemContext(toscaItemId){
	var tosca = $("#solution_script_editor_form_text").val();
	tosca = jsyaml.load(tosca);
	var topology_template = tosca.topology_template;
	var node_templates = topology_template.node_templates;
	var node = node_templates[toscaItemId];
	$("#edit_item_form_name").val(toscaItemId);
	$("#edit_item_form_type").val(node.type);
	var id = null;
	var properties = "";
	if (typeof node.properties != 'undefined'){
		if (node.properties != null){
			for (var i = 0; i < node.properties.length; i++){
				$.each(node.properties[i], function(index, value) {
					if (index == "id"){
						id = value;
					}
					properties += "<tr><td>"+index+"</td><td>"+value +"</td></tr>";
				});
			}
		}
	}
	$("#edit_item_form_properties").html(properties);
	//html += "Description "+tosca.description;
	$("#edit_item_form_class_field").hide();
	$("#edit_item_form_category_field").hide();
	$("#edit_item_form_target_id").val("");
	$("#edit_item_form_display_target").hide();//prop('disabled', true);
	if (id != null){
		$.getJSON( "api/element.php?id="+id, function(result) {
			if (result.elements.length != 0){
				var element = result.elements[0];
				$("#edit_item_form_class_field").show();
				$("#edit_item_form_category_field").show();
				$("#edit_item_form_class").val(element.class.name);
				$("#edit_item_form_category").val(element.category.name);
				$("#edit_item_form_target_id").val(id);
				if (element.category.name == "solution"){
					$("#edit_item_form_display_target").show();//prop('disabled', false);
				} else if (element.category.name == "process"){
					$("#edit_item_form_display_target").show();//prop('disabled', false);
				} else if (element.category.name == "actor"){
					$("#edit_item_form_display_target").show();//prop('disabled', false);
				}
			}
		});
	}
	$("#edit_item_form").dialog({"modal":true,"title":"Edition d'un élément","minWidth":500});
}
function showToscaTargetItem(itemId,itemCategory){
	if (itemCategory == "solution"){
		displaySolution(itemId);
	} else if (itemCategory == "process"){
		displayProcess(itemId);
	} else if (itemCategory == "actor"){
		displayBusiness(itemId);
	} else {
		alert("showToscaTargetItem() : J'ai oublié de traiter ce type de categorie "+itemCategory);
	}
}
