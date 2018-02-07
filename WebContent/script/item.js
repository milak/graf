var datatableItems = null;
var itemClasses = null;
var functionToCallWhenAddClicked = null;
function openImportItemForm(aFunctionToCallWhenAddClicked){
	functionToCallWhenAddClicked = aFunctionToCallWhenAddClicked;
	$.getJSON( "api/view.php?view=logical", function(result) {
		var areaList = buildAreaList(result.view);
		$('#import_item_form_area').html(areaList);
		$('#import_item_form_create_area').html(areaList);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
	$.getJSON( "api/element_class.php", function(result) {
		itemClasses = new Array();
		var categories = result.categories;
		var htmlClassesCreate = "";
		var htmlCategoryCreate = "";
		var htmlClassesSearch = "<option value='NULL'>~~Toutes les classes~~</option>";
		var htmlCategorySearch = "<option value='NULL'>~~Toutes les catégories~~</option>";
		for (var i = 0; i < categories.length; i++){
			var category = categories[i];
			htmlCategorySearch += "<option value='"+category.id+"'>"+category.name+"</option>";
			htmlCategoryCreate += "<option value='"+category.id+"'>"+category.name+"</option>";
			for (var j = 0; j < category.classes.length; j++){
				var classe = category.classes[j];
				if (classe.abstract == "false"){
					htmlClassesCreate += "<option value='"+classe.id+"'>"+classe.name+"</option>";
				}
				htmlClassesSearch += "<option value='"+classe.id+"'>"+classe.name+"</option>";
				itemClasses[classe.id] = classe;
			}
		}
		$("#import_item_form_category").html(htmlCategorySearch);
		$("#import_item_form_class").html(htmlClassesSearch);
		$("#import_item_form_create_category").html(htmlCategoryCreate);
		$("#import_item_form_create_class").html(htmlClassesCreate);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
	$("#import_item_form").dialog({"modal":true,"title":"Chercher un élément","minWidth":1100,"minHeight":800});
}
function applyCategory(categoryList,classList,create){
	var categoryName = $("#"+categoryList).val();
	$.getJSON( "api/element_class.php", function(result) {
		var categories = result.categories;
		var first = null;
		var count = 0;
		var htmlClasses = "";
		if (!create){
			htmlClasses = "<option value='NULL'>~~Toutes les classes~~</option>"
		}
		for (var i = 0; i < categories.length; i++){
			var category = categories[i];
			if (categoryName != "NULL"){
				if (categoryName != category.name){
					continue;
				}
			}
			for (var j = 0; j < category.classes.length; j++){
				var classe = category.classes[j];
				if (create && (classe.abstract == "true")){
					continue;
				}
				first = classe.id;
				count++;
				htmlClasses += "<option value='"+classe.id+"'>"+classe.name+"</option>";
			}
		}
		$("#"+classList).html(htmlClasses);
		if (count == 1){
			$("#"+classList).val(first);
		}
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function onImportItemFormCreateClick(){
	var name 		= $("#import_item_form_create_name").val();
	if (name == ""){
		alert("Vous devez saisir un nom");
		return;
	}
	var className 	= $("#import_item_form_create_class").val();
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
			onImportItemFormSearchClick();
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
			currentItem.refresh();
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
			currentItem.refresh();
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function onImportItemFormSearchClick(){
	if (datatableItems == null){
		datatableItems = $("#import_item_form_result").dataTable();
	}
	var url = "api/element.php";
	var selectClass = $("#import_item_form_class").val();
	if (selectClass != "NULL"){
		url += "?class_name="+selectClass;
	} else {
		var selectCategorie = $("#import_item_form_category").val();
		if (selectCategorie != "NULL"){
			url += "?category_name="+selectCategorie;
		}
	}
	datatableItems.fnClearTable();
	$.getJSON( url, function(result) {
		var selectName = $("#import_item_form_name").val().trim();
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
			var label = "<button onClick='event.preventDefault();onImportItemFormAddClick(\""+element.id+"\");'>Ajouter</button>";
			label    += "<button onClick='event.preventDefault();deleteItem(\""+element.id+"\");'>Effacer</button>";
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
function onImportItemFormAddClick(id){
	if (functionToCallWhenAddClicked == null){
		currentItem.addItem(id);
	} else {
		functionToCallWhenAddClicked(id);
	}
}
function showItemContext(id){
	$.getJSON( "api/element.php?id="+id, function(result) {
		var element = result.elements[0];
		var html = "<p>"+element.category.name+"</p>";
		html += "<b>Nom</b> : "+element.name+"<br/><br/>";
		html += "<b>Classe</b> : "+element.class.name+"<br/><br/>";
		html += "<hr/>";
		html += " <button onclick='hidePopup();displaySolution(\""+id+"\")'><img src='images/63.png'/> ouvrir</button>";
		if (currentItem != null){
			html += " <button onclick='hidePopup();removeItem(\""+currentItem.id+"\",\""+id+"\")'><img src='images/14.png'/> retirer</button>";
		}
		html += " <button onclick='hidePopup();deleteItem(\""+id+"\")'><img src='images/14.png'/> supprimer</button>";
		html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
		showPopup("Détail",html);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function showToscaItemContext(toscaItemId){
	var tosca = $("#solution_script_editor_form_text").val();
	tosca = jsyaml.load(tosca);
	var topology_template = tosca.topology_template;
	var node_templates = topology_template.node_templates;
	var node = node_templates[toscaItemId];
	$("#edit_item_form_name").val(toscaItemId);
	var properties = "";
	var id = null;
	if (node != null){ // L'item se trouve dans TOSCA
		$("#edit_item_form_type").val(node.type);
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
		$("#edit_item_form_remove_item").hide();
		$("#edit_item_form_delete_tosca_item").show();
		$("#edit_item_form_add_item").hide();
		if (id == null){
			$("#edit_item_form_title").text("Item se trouvant dans Tosca mais ne se trouvant pas dans la base");
		} else {
			$("#edit_item_form_title").text("Item se trouvant dans Tosca");
		}
	} else {
		id = toscaItemId;
		properties += "<tr><td>id</td><td>"+id +"</td></tr>";
		$("#edit_item_form_remove_item").show();
		$("#edit_item_form_delete_tosca_item").hide();
		$("#edit_item_form_add_item").show();
		$("#edit_item_form_type").val("N/A");
		$("#edit_item_form_title").text("Item ne se trouvant pas dans Tosca mais rattaché à la solution");
	}
	$("#edit_item_form_properties").html(properties);
	//html += "Description "+tosca.description;
	$("#edit_item_form_class_field").hide();
	$("#edit_item_form_category_field").hide();
	$("#edit_item_form_target_id").val("");
	$("#edit_item_form_display_target").hide();
	
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
	$("#edit_item_form").dialog({"modal":true,"title":"Edition d'un élément","minWidth":600});
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
