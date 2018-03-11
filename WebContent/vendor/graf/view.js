var viewDescription = new Array();
var views = new Array();
global.view = {
	open : function(viewName){
		var viewPanel;
		var description = viewDescription[viewName];
	   	if (description != null){
			var panel = {
				title 	: description.title,
	    		class 	: description.class,
				done	: function(){
					if (this.viewDescription.svg) {
						try{
							this.svgPanZoom.destroy();
						}catch(exception){}
						try{
							this.svgPanZoom.destroy();
						}catch(exception){}
						try{
							this.svgPanZoom = svgPanZoom("#"+viewName, {
							    zoomEnabled				: true,
							    dblClickZoomEnabled		: false,
							    controlIconsEnabled		: true,
							    fit						: true,
							    center					: false,
							    minZoom					: 0.1,
							    zoomScaleSensitivity 	: 0.3
							});
						}catch(exception){}
					}
				},
				fail : function(jxqr,textStatus,error){
	    			sendMessage("error",i18next.t("message.unable_to_load_view")+" : " + error);
				},
				update	: function(){
					if (this.viewDescription.svg) {
						try{
							this.svgPanZoom.destroy();
						}catch(exception){}
						try{
							this.svgPanZoom.destroy();
						}catch(exception){}
						try{
							this.svgPanZoom = svgPanZoom("#"+viewName, {
							    zoomEnabled				: true,
							    dblClickZoomEnabled		: false,
							    controlIconsEnabled		: true,
							    fit						: true,
							    center					: false,
							    minZoom					: 0.1,
							    zoomScaleSensitivity 	: 0.3
							});
						}catch(exception){}
					}
				},
				close  : function(){
					// retirer la vue
	    			var index = -1;
	    			for(var i = 0; i < views.length; i++){
	                	var view = views[i];
	                	if (view.panelId == this.panelId){
	                    	index = i;
	                    	break;
	                	}
	    			}
	    			if (index != -1){
	    				views.splice(index,1);
	    			}
				}
			}
			panel.url = _buildURL(description);
	   		viewPanel = $('#'+viewName).panel(panel);
			viewPanel.viewDescription = description;
	   		views.push(viewPanel);
		} else {
	    	sendMessage("error",i18next.t("message.unknown_view") + " : " + viewName);
		}
	},
	applyItem(aItem){
		for (var i = 0; i < views.length; i++){
        	var view = views[i];
        	var desc = view.viewDescription;
        	if (desc.svg){
        		for (var p = 0; p < desc.params.length; p++){
        			if (desc.params[p].name == "itemId"){
        				view.reload(_buildURL(desc));
        				break;
        			}
        		}
        	}
    	}
	},
	applyDocument(aDocument){
		for (var i = 0; i < views.length; i++){
        	var view = views[i];
        	var desc = view.viewDescription;
        	if (desc.svg){
        		for (var p = 0; p < desc.params.length; p++){
        			if (desc.params[p].name == "documentId"){
        				view.reload(_buildURL(desc));
        				break;
        			}
        		}
        	}
    	}
	},
	init : function(){
		$.getJSON("views/views.json",function (result){
			var views = result.views;
			var main = $("#main");
			for (var v = 0; v < views.length; v++){
				var view = views[v];
				view.title = i18next.t("view."+view.title);
				viewDescription[view.name] = view;
				if (view.svg){
					main.append("<svg id='"+view.name+"' style='width:100%;height:100%;display:none'></svg>");
				} else {
					main.append("<div id='"+view.name+"' style='width:100%;height:100%;display:none'></div>");
				}
			}
			var menuViewDropDown = $("#menuViewDropDown");
			Object.keys(viewDescription).forEach(function (index) {
				var viewHTML = "<a class='dropdown-item' href='#' onClick='global.view.open(\""+index+"\")'><img src='images/"+viewDescription[index].icon+"'/> "+viewDescription[index].title+"</a>";
				menuViewDropDown.append($(viewHTML));
			});
		}).fail(function(jxqr,textStatus,error){
			sendMessage("error",i18next.t("message.unable_to_load_view")+" : " + error);
		});
	}	
};
function _buildURL(description){
	var url = description.url;
  	var car = "?";
    if (url.indexOf("?") != -1){
    	car = "&";
    }
	var result = "";
	var current;
	var first = true;
	var params = description.params;
	for (var p = 0; p < params.length; p++){
		if (!first){
			result += "&";
		}
		var param = params[p];
		if (param.name == "itemId"){
			current = global.item.getCurrent();
			if (current != null){
				result += "itemId="+current.id;
				first = false;
			} else if (param.usage == "required"){
				if (description.svg) {
					return null;
				}
			}
		} else if (param.name == "documentId"){
			current = global.document.getCurrent();
			if (current != null){
				result += "documentId="+current.id;
				first = false;
			} else if (param.usage == "required"){
				if (description.svg) {
					return null;
				}
			}
		}
	}
	return url + car + result;
}
function searchDocument(){
	$("#searchDocument").panel({
    	url : 'forms/searchDocument.html',
    	class : 'panel-green',
    	title : i18next.t("view.document_search")
    });
}
function createDocument(){
	$("#createDocument").panel({
    	url 	: 'forms/createDocument.html',
    	class 	: 'panel-green',
    	title 	: i18next.t("view.document_create")
    });
}
function createItem(){
	$("#createItem").panel({
    	url 	: 'forms/createItem.html?id='+Math.random(),
    	class 	: 'panel-green',
    	title 	: i18next.t("view.item_create")
    });
}
function searchItem(){
	$("#searchItem").panel({
    	url : 'forms/searchItem.html',
    	class : 'panel-green',
    	title : i18next.t("view.item_search")
    });
}