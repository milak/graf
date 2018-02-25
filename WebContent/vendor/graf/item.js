var itemsList = new Array();
function home(){
	global.currentItem = null;
	itemsList = new Array();
	applyItem(null);
}
function breadCrumbItem(id){
	var newList = new Array();
	for (var i = 0; i < itemsList.length; i++){
		if (itemsList[i].id == id){
			global.currentItem = itemsList[i];
			break;
		}
		newList.push(itemsList[i]);
	}
	itemsList = newList;
	applyItem(global.currentItem);
}
function previousItem(){
	if (itemsList.length > 0){
		global.currentItem = itemsList.pop();
		applyItem(global.currentItem);
	} else {
		global.currentItem = null;
		applyItem(null);
	}
}
function openItem(item){
	if (global.currentItem != null){
		if (global.currentItem.id == item.id){
			return;
		}
		itemsList.push(global.currentItem);
	}
	global.currentItem = item;
	applyItem(item);
}
function refresh(){
	applyItem(global.currentItem);
}
function _refreshBreadCrumb(){
	var html = "";
	if (global.currentItem == null){
		html += '<li class="breadcrumb-item active">No item selected</li>';
	} else {
		var start = 0;
		html += '<li class="breadcrumb-item"><a href="#" onclick="home()">Home</a></li>';
		if (itemsList.length > 5){
			start = itemsList.length - 5;
			html += '<li class="breadcrumb-item">...</li>';
		}
		for (var i = start; i < itemsList.length; i++){
			html += '<li class="breadcrumb-item"><a href="#"';
			html += ' title="'+itemsList[i].name+'" ';
			html += 'onClick="breadCrumbItem(\''+itemsList[i].id+'\')">';
			if (itemsList[i].name.length > 15){
				html += itemsList[i].name.substring(0,5);
				html += '...';
				html += itemsList[i].name.substring(itemsList[i].name.length-5);
			} else {
				html += itemsList[i].name;
			}
			html += '</a></li>';
		}
		html += '<li class="breadcrumb-item active">';
		html += ''+global.currentItem.name+'</li>';
	}
	$("#breadcrumb").html(html);
}
function applyItem(item){
	_refreshBreadCrumb();
	if (item != null){
		$.getJSON( "api/element.php?id="+item.id, function(result) {
			if (result.elements.length == 0){
    			openItem(null);
    			sendMessage("warning","Item doesn't exist");
			} else {
    			var item = result.elements[0];
    			global.currentItem = item;
    			$("#menuCurrentItem").html(item.category.name+" - "+item.name);
    			$("#menuDeleteItem").attr("disabled", false);
    			$("#menuDeleteItem").attr('class', "dropdown-item");
    			for (var i = 0; i < views.length; i++){
                	var view = views[i];
                	if (!view.viewDescription.static){
                		var car = "?";
                   		if (view.viewDescription.url.indexOf("?") != -1){
                       		car = "&";
                   		}
               			view.reload(view.viewDescription.url+car+"id="+item.id);
                	}
            	}
    			/** Apply currentItem change */
            	$("*[data-provider^='currentItem']").each(function(index,listener){
                	listener = $(listener);
                	var attribute = listener.attr("data-provider");
                	if (attribute == "currentItem.name"){
                		listener.text(item.name);
                	} else if (attribute == "currentItem.class.name"){
                		listener.text(item.class.name);
                	} else if (attribute == "currentItem.category.name"){
                		listener.text(item.category.name);
                	}
        			listener.trigger("change");
            	});
			}
		}).fail(function(jxqr,textStatus,error) {
			sendMessage("error","Unable to get item information : "+error);
		});
	} else {
		$("#menuCurrentItem").html("No item selected");
		$("#menuDeleteItem").attr("disabled", true);
		$("#menuDeleteItem").attr('class', "dropdown-item disabled");
		for (var i = 0; i < views.length; i++){
        	var view = views[i];
        	if (view.viewDescription.noItemSupported){
        		view.reload(view.viewDescription.url);
        	} else if (!view.viewDescription.static){
        		view.reload(null);
        	} else {
           		
        	}
    	}
		/** Apply currentItem change */
    	$("*[data-provider^='currentItem']").each(function(index,listener){
        	listener = $(listener);
        	var attribute = listener.attr("data-provider");
        	var changed = false;
       		listener.text("");
       		changed = true;
   			listener.trigger("change");
    	});
	}
}
/**
 * Delete currentItem
 */
function deleteItem(){
	var item = global.currentItem;
	if (item == null){
		return;
	}
	if (confirm("Do you really want to delete " + item.category.name + " called '" + item.name + "' ?")){
		$.ajax({
			type 	: "DELETE",
			url 	: "api/element.php?id="+item.id,
			dataType: "text",
			success	: function(data) {
				previousItem();
				sendMessage("success","Item successfully deleted");
			}
		}).fail(function(jxqr,textStatus,error){
			sendMessage("error","Unable to delete current item : "+error);
		});
	}
}
function linkItem(parentItem,childItem){
	// Ajouter l'item
	$.ajax({
		type 	: "POST",
		url 	: "api/element.php",
		data	: {
			"id"		: parentItem.id,
			"child_id"	: childItem.id
		},
		dataType: "text",
		success	: function( data ) {
			applyItem(global.currentItem);
			sendMessage("success","Item successfully linked");
		}
	}).fail(function(jxqr,textStatus,error){
		sendMessage("error","Unable to link item : "+error);
	});
}
function unlinkItem(parentItem,childItem){
   	$.ajax({
   		type 	: "DELETE",
   		url 	: "api/element.php?id="+parentItem.id+"&child_id="+childItem.id,
   		dataType: "text",
   		success	: function(data) {
   			applyItem(global.currentItem);
   		}
   	}).fail(function(jxqr,textStatus,error){
       	sendMessage("error","Unable to unlink item : " + error);
   	});
}   	


    	
    	
    	
/**
 * function showToscaItemContext(toscaItemId){ var tosca =
 * $("#solution_script_editor_form_text").val(); tosca = jsyaml.load(tosca); var
 * topology_template = tosca.topology_template; var node_templates =
 * topology_template.node_templates; var node = node_templates[toscaItemId];
 * $("#edit_item_form_name").val(toscaItemId); var properties = ""; var id =
 * null; if (node != null){ // L'item se trouve dans TOSCA
 * $("#edit_item_form_type").val(node.type); if (typeof node.properties !=
 * 'undefined'){ if (node.properties != null){ for (var i = 0; i <
 * node.properties.length; i++){ $.each(node.properties[i], function(index,
 * value) { if (index == "id"){ id = value; } properties += "
 * <tr>
 * <td>"+index+"</td>
 * <td>"+value +"</td>
 * </tr>"; }); } } } $("#edit_item_form_remove_item").hide();
 * $("#edit_item_form_delete_tosca_item").show();
 * $("#edit_item_form_add_item").hide(); if (id == null){
 * $("#edit_item_form_title").text("Item se trouvant dans Tosca mais ne se
 * trouvant pas dans la base"); } else { $("#edit_item_form_title").text("Item
 * se trouvant dans Tosca"); } } else { id = toscaItemId; properties += "
 * <tr>
 * <td>id</td>
 * <td>"+id +"</td>
 * </tr>"; $("#edit_item_form_remove_item").show();
 * $("#edit_item_form_delete_tosca_item").hide();
 * $("#edit_item_form_add_item").show(); $("#edit_item_form_type").val("N/A");
 * $("#edit_item_form_title").text("Item ne se trouvant pas dans Tosca mais
 * rattaché à la solution"); } $("#edit_item_form_properties").html(properties);
 * //html += "Description "+tosca.description;
 * $("#edit_item_form_class_field").hide();
 * $("#edit_item_form_category_field").hide();
 * $("#edit_item_form_target_id").val("");
 * $("#edit_item_form_display_target").hide();
 * 
 * if (id != null){ $.getJSON( "api/element.php?id="+id, function(result) { if
 * (result.elements.length != 0){ var element = result.elements[0];
 * $("#edit_item_form_class_field").show();
 * $("#edit_item_form_category_field").show();
 * $("#edit_item_form_class").val(element.class.name);
 * $("#edit_item_form_category").val(element.category.name);
 * $("#edit_item_form_target_id").val(id); if (element.category.name ==
 * "solution"){ $("#edit_item_form_display_target").show();//prop('disabled',
 * false); } else if (element.category.name == "process"){
 * $("#edit_item_form_display_target").show();//prop('disabled', false); } else
 * if (element.category.name == "actor"){
 * $("#edit_item_form_display_target").show();//prop('disabled', false); } } }); }
 * $("#edit_item_form").dialog({"modal":true,"title":"Edition d'un
 * élément","minWidth":600}); } function
 * showToscaTargetItem(itemId,itemCategory){ if (itemCategory == "solution"){
 * displaySolution(itemId); } else if (itemCategory == "process"){
 * displayProcess(itemId); } else if (itemCategory == "actor"){
 * displayBusiness(itemId); } else { alert("showToscaTargetItem() : J'ai oublié
 * de traiter ce type de categorie "+itemCategory); } }
 */
