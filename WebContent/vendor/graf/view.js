var viewDescription = new Array();
var views = new Array();
function loadViews(){
	viewDescription['strategic'] 	= { url : 'views/view_strategique.php', 	icon  : '63.png', class : 'panel-purple', noItemSupported : true, static : false,	svg : true, title : i18next.t("view.strategical")};
	viewDescription['business'] 	= { url : 'views/view_business.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : i18next.t("view.business")};
	viewDescription['logical'] 		= { url : 'views/view_logique.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : i18next.t("view.logical")};
	viewDescription['service'] 		= { url : 'views/view_service.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Service view"};
	viewDescription['process'] 		= { url : 'views/view_process.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Process view"};
	viewDescription['technical']	= { url : 'views/view_technical.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Technical view"};
	viewDescription['viewItem']		= { url : 'forms/viewItem.html', 			icon  : '2.png' , class : 'panel-purple', noItemSupported : false, static : true,  svg : false, 	title : i18next.t("view.item_detail")};
	viewDescription['viewDocument']	= { url : 'forms/viewDocument.html',		icon  : '2.png' , class : 'panel-purple', noItemSupported : false, static : true,  svg : false, 	title : i18next.t("view.document_detail")};
	viewDescription['mapEurope']	= { url : 'views/view_map.php?map=europe', 	icon  : '77.png', class : 'panel-purple', noItemSupported : true, static : false,  svg : true, 	title : i18next.t("view.map.europe")};
	viewDescription['mapWorld']		= { url : 'views/view_map.php?map=world', 	icon  : '77.png', class : 'panel-purple', noItemSupported : true, static : false,  svg : true, 	title : i18next.t("view.map.world")};
	var menuViewDropDown = $("#menuViewDropDown");
	Object.keys(viewDescription).forEach(function (index) {
		menuViewDropDown.append($("<a class='dropdown-item' href='#' onClick='addView(\""+index+"\")'><img src='images/"+viewDescription[index].icon+"'/> "+viewDescription[index].title+"</a>"));
	});
}
function searchDocument(){
	$("#searchDocument").panel({
    	url : 'forms/searchDocument.html',
    	class : 'panel-green',
    	title : "Search a document"
    });
}
function createDocument(){
	sendMessage("warning",i18next.t("message.not_yet_implemented"));
}
function createItem(){
	$("#createItem").panel({
    	url 	: 'forms/createItem.html?id='+Math.random(),
    	class 	: 'panel-green',
    	title 	: "Create an item"
    	//buttons : ["reload","close"]
    });// .state("maximized");
}
function searchItem(){
	$("#searchItem").panel({
    	url : 'forms/searchItem.html',
    	class : 'panel-green',
    	title : "Search an item"
    	// buttons : ["reload","close"]
    });// .state("maximized");
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