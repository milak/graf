var viewDescription = new Array();
viewDescription['strategic'] 	= { url : 'views/view_strategique.php', 	icon  : '63.png', class : 'panel-purple', noItemSupported : true, static : false,	svg : true, 	title : "Strategical view"};
viewDescription['business'] 	= { url : 'views/view_business.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Business view"};
viewDescription['logical'] 		= { url : 'views/view_logique.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Logical view"};
viewDescription['service'] 		= { url : 'views/view_service.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Service view"};
viewDescription['process'] 		= { url : 'views/view_process.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Process view"};
viewDescription['technical']	= { url : 'views/view_technical.php', 		icon  : '63.png', class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Technical view"};
viewDescription['viewItem']		= { url : 'forms/viewItem.html', 			icon  : '2.png' , class : 'panel-purple', noItemSupported : false, static : true,  svg : false, 	title : "Detail"};
viewDescription['mapEurope']	= { url : 'views/view_map.php?map=europe', 	icon  : '77.png', class : 'panel-purple', noItemSupported : true, static : false,  svg : true, 	title : "Map - Europe"};
viewDescription['mapWorld']		= { url : 'views/view_map.php?map=world', 	icon  : '77.png', class : 'panel-purple', noItemSupported : true, static : false,  svg : true, 	title : "Map - World"};
var views = new Array();
function createItem(){
	$("#createItem").panel({
    	url 	: 'forms/createItem.html?id='+Math.random(),
    	class 	: 'panel-green',
    	title 	: "Create an item",
    	buttons : ["reload","close"]
    });// .state("maximized");
}
function searchItem(){
	$("#searchItem").panel({
    	url : 'forms/searchItem.html',
    	class : 'panel-green',
    	title : "Search an item",
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
    			sendMessage("error","Unable to load view : " + error);
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
       		if (global.currentItem != null){
           		var car = "?";
           		if (url.indexOf("?") != -1){
               		car = "&";
           		}
        	   	panel.url = url + car + "id="+global.currentItem.id;
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
    	alert("Vue non supportée " + view);
	}
}