global.item = {
	_currentItem : null,
	getCurrent : function(){
		return this._currentItem;
	},
	setCurrent : function(aItem){
		this._currentItem = aItem;
		this.refresh();
	},
	open : function(aItemId){
		if (aItemId != null){
			$.getJSON( "api/item.php?id="+aItemId, function(result) {
				if (result.code != 0){
					sendMessage("error",i18next.t("message.item_no_information")+" : "+result.message);
				} else if (result.objects.length == 0){
	    			global.item.open(null);
	    			sendMessage("warning",i18next.t("message.item_not_exist"));
				} else {
	    			var item = result.objects[0];
	    			// Open the item
	    			var currentItem = global.item.getCurrent();
	    			if (currentItem != null){
	    				if (currentItem.id == item.id){
	    					return;
	    				}
	    				breadCrumb.add(currentItem);
	    			}
	    			breadCrumb.remove(item);
	    			global.item.setCurrent(item);
				}
			}).fail(function(jxqr,textStatus,error) {
				sendMessage("error",i18next.t("message.item_no_information")+" : "+error);
			});
		}
	},
	refresh : function(){
		this._displayItem(this._currentItem);
	},
	_displayItem : function(item){
		breadCrumb.refresh();
		if (item != null){
			$.getJSON( "api/item.php?id="+item.id, function(result) {
				if (result.code != 0){
					sendMessage("error",i18next.t("message.item_no_information")+" : "+result.message);
				} else if (result.objects.length == 0){
	    			this.open(null);
	    			sendMessage("warning",i18next.t("message.item_not_exist"));
				} else {
	    			var item = result.objects[0];
	    			this._currentItem = item;
	    			$("#menuDeleteItem").attr("disabled", false);
	    			$("#menuDeleteItem").attr('class', "dropdown-item");
	    			global.view.applyItem(item);
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
				sendMessage("error",i18next.t("message.item_no_information")+" : "+error);
			});
		} else {
			$("#menuDeleteItem").attr("disabled", true);
			$("#menuDeleteItem").attr('class', "dropdown-item disabled");
			global.view.applyItem(item);
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
	},
	"link" : function(parentItem,childItem){
		// Ajouter l'item
		$.ajax({
			type 	: "POST",
			url 	: "api/item.php",
			data	: {
				"id"		: parentItem.id,
				"child_id"	: childItem.id
			},
			dataType: "json",
			success	: function( result ) {
				if (result.code == 0){
					global.item.refresh();
					sendMessage("success",i18next.t("message.item_success_link"));
				} else {
					sendMessage("error",i18next.t("message.item_failure_link")+" : "+result.message);
				}
			}
		}).fail(function(jxqr,textStatus,error){
			sendMessage("error",i18next.t("message.item_failure_link")+" : "+error);
		});
	},
	"unlink" : function(parentItem,childItem){
	   	$.ajax({
	   		type 	: "DELETE",
	   		url 	: "api/item.php?id="+parentItem.id+"&child_id="+childItem.id,
	   		dataType: "json",
	   		success	: function(result) {
	   			if (result.code != 0){
					sendMessage("error",i18next.t("message.item_failure_unlink")+" : "+result.message);
				} else {
					global.item.refresh();
		   			sendMessage("success",i18next.t("message.item_success_unlink"));
				}
	   		}
	   	}).fail(function(jxqr,textStatus,error){
	       	sendMessage("error",i18next.t("message.item_failure_unlink") +" : " + error);
	   	});
	},
	/**
	 * Delete currentItem
	 */
	"delete" : function (){
		var item = this.getCurrent();
		if (item == null){
			sendMessage("warning",i18next.t("message.no_item_selected"));
			return;
		}
		// TODO voir comment on ajoute des champs dans un template :  + " + item.category.name + " called '" + item.name + "' ?"
		if (confirm(i18next.t("message.item_delete_confirm"))){
			$.ajax({
				type 	: "DELETE",
				url 	: "api/item.php?id="+item.id,
				dataType: "json",
				success	: function(result) {
					if (result.code != 0){
						sendMessage("error",i18next.t("message.item_failure_delete")+" : "+result.message);
					} else {
						global.breadCrumb.previous();
						sendMessage("success",i18next.t("message.item_success_delete"));
					}
				}
			}).fail(function(jxqr,textStatus,error){
				sendMessage("error",i18next.t("message.item_failure_delete")+" : "+error);
			});
		}
	}
};
var breadCrumb = {
	_itemsList : new Array(),
	remove : function(item){
		var newList = new Array();
		for (var i = 0; i < this._itemsList.length; i++){
			var anItem = this._itemsList[i];
			if (anItem.id != item.id){
				newList.push(anItem);
			}
		}
		this._itemsList = newList;
	},
	add : function(item){
		this.remove(item);
		this._itemsList.push(item);
	},
	home : function(){
		this._itemsList = new Array();
		global.item.setCurrent(null);
	},
	selectItem : function(index){
		var item = this._itemsList[index];
		var newList = new Array();
		for (var i = 0; i < index; i++){
			newList.push(this._itemsList[i]);
		}
		this._itemsList = newList;
		global.item.setCurrent(item);
	},
	previous : function(){
		if (this._itemsList.length > 0){
			global.item.setCurrent(this._itemsList.pop());
		} else {
			global.item.setCurrent(null);
		}
	},
	refresh : function(){
		var html = "";
		var currentDocument = global.document.getCurrent();
		var currentItem = global.item.getCurrent();
		if (currentItem == null){
			if (currentDocument != null){
				html += '<li class   = "breadcrumb-item active">';
				html += '  <img src  = "images/2.png"/>';
				html += '  Document : '+currentDocument.name;
				html += '</li>';
			} else {
				html += '<li class   = "breadcrumb-item active">'+i18next.t("breadcrumb.no_item")+'</li>';
			}
		} else {
			var start = 0;
			html += '<li class       = "breadcrumb-item"><a href="#" onclick="breadCrumb.home()">'+i18next.t("breadcrumb.home")+'</a></li>';
			if (this._itemsList.length > 5){
				start = this._itemsList.length - 5;
				html += '<li class   = "breadcrumb-item">...</li>';
			}
			for (var i = start; i < this._itemsList.length; i++){
				html += '<li class   = "breadcrumb-item">';
				html += '  <a href   = "#"';
				html += '    title   = "'+this._itemsList[i].name+'"';
				html += '    onClick = "breadCrumb.selectItem('+i+')">';
				html += this._formatName(this._itemsList[i].name);
				html += '  </a>';
				html += '</li>';
			}
			html += '<li class="breadcrumb-item active">';
			html += ''+currentItem.category.name+' - ';
			html += ''+currentItem.name+'</li>';
			if (currentDocument != null){
				html += '<li class   = "breadcrumb-item active">';
				html += '  <img src  = "images/2.png"/>';
				html += '  Document : ' + this._formatName(currentDocument.name);
				html += '</li>';
			}
		}
		$("#breadcrumb").html(html);
	},
	_formatName : function(aName){
		if (aName.length > 15){
			return aName.substring(0,5)+'..'+aName.substring(aName.length-5);
		} else {
			return aName;
		}
	}
};



 	


    	
    	
    	
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
 * if (id != null){ $.getJSON( "api/item.php?id="+id, function(result) { if
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
