var viewDescription = new Array();
var views = new Array();
function loadViews(){
	/*viewDescription['strategic'] 	= { url : 'views/view_strategique.php', 	icon  : '63.png', class : 'panel-purple', noItemSupported : true, static : false,	svg : true, title : i18next.t("view.strategical")};
	viewDescription['business'] 	= { url : 'views/view_business.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : i18next.t("view.business")};
	viewDescription['logical'] 		= { url : 'views/view_logique.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : i18next.t("view.logical")};
	viewDescription['service'] 		= { url : 'views/view_service.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Service view"};
	viewDescription['process'] 		= { url : 'views/view_process.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Process view"};
	viewDescription['technical']	= { url : 'views/view_technical.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Technical view"};
	viewDescription['viewItem']		= { url : 'forms/viewItem.html', 			icon  : '2.png' , class : 'panel-purple', noItemSupported : false, static : true,  svg : false, 	title : i18next.t("view.item_detail")};
	viewDescription['viewDocument']	= { url : 'forms/viewDocument.html',		icon  : '2.png' , class : 'panel-purple', noItemSupported : false, static : true,  svg : false, 	title : i18next.t("view.document_detail")};
	viewDescription['mapEurope']	= { url : 'views/view_map.php?map=europe', 	icon  : '77.png', class : 'panel-purple', noItemSupported : true, static : false,  svg : true, 	title : i18next.t("view.map.europe")};
	viewDescription['mapWorld']		= { url : 'views/view_map.php?map=world', 	icon  : '77.png', class : 'panel-purple', noItemSupported : true, static : false,  svg : true, 	title : i18next.t("view.map.world")};*/
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
			menuViewDropDown.append($("<a class='dropdown-item' href='#' onClick='addView(\""+index+"\")'><img src='images/"+viewDescription[index].icon+"'/> "+viewDescription[index].title+"</a>"));
		});
	}).fail(function(jxqr,textStatus,error){
		sendMessage("error","Unable to load views : " + error);
	});
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

function addView(viewName){
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
        			}catch(exception){
            			// console.log(exception);
            		}
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
       	var url = description.url;
       	if (!description.static) {
       		var currentItem = global.item.getCurrent();
       		if (currentItem != null){
           		var car = "?";
           		if (url.indexOf("?") != -1){
               		car = "&";
           		}
        	   	panel.url = url + car + "id="+currentItem.id;
       		} else if (description.noItemSupported){
       			panel.url = url;
       		} else {
       			panel.url = null;
       		}
       	} else {
       		panel.url = url;
       	}
   		viewPanel = $('#'+viewName).panel(panel);
		viewPanel.viewDescription = description;
   		views.push(viewPanel);
	} else {
    	sendMessage("error",i18next.t("message.unknown_view") + " : " + viewName);
	}
}