<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="milak">
<link rel="icon" href="images/favicon.ico">
<title>GRAF - Graphic Rendering Architecture Framework</title>
<link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.css" rel="stylesheet">
<link rel="stylesheet" href="vendor/jquery-panel/css/jquery-panel.css" />
<link rel="stylesheet" href="vendor/jquery/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="vendor/datatable/jquery.dataTables.min.css">
<link rel="stylesheet" href="vendor/font-awsome/css/font-awesome.min.css" />
<style type="text/css">
html {
	height: 100%;
	overflow: hidden;
}
/** Styles liés à typeAHead */
.tt-menu {
  //width: 80px;
  margin: 5px 0;
  padding: 8px 0;
  background-color: #fff;
  border: 1px solid #ccc;
  border: 1px solid rgba(0, 0, 0, 0.2);
  -webkit-border-radius: 4px;
     -moz-border-radius: 4px;
          border-radius: 4px;
  -webkit-box-shadow: 0 5px 10px rgba(0,0,0,.2);
     -moz-box-shadow: 0 5px 10px rgba(0,0,0,.2);
          box-shadow: 0 5px 10px rgba(0,0,0,.2);
}
.tt-suggestion {
  padding: 3px 20px;
}
.tt-suggestion:hover {
  cursor: pointer;
  color: #fff;
  background-color: #0097cf;
}
.tt-suggestion.tt-cursor {
  color: #fff;
  background-color: #0097cf;
}
</style>
</head>
<body oncontextmenu="event.preventDefault()" onresize="applyItem(global.currentItem)">
	<header>
		<!-- Fixed navbar -->
		<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
			<a class="navbar-brand" href="#" onClick="home()"
				title="Graphic Rendering Architecture Framework">GRAF</a>
			<button class="navbar-toggler" type="button" data-toggle="collapse"
				data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false"
				aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarCollapse">
				<ul class="navbar-nav mr-auto">
					<li class="nav-item active"><a class="nav-link" href="#" onClick="previousItem()">Back <span
							class="sr-only">(current)</span></a></li>
					<li class="nav-item dropdown"><a class="nav-link dropdown-toggle"
						href="http://example.com" id="menuItem" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Item</a>
						<div class="dropdown-menu" aria-labelledby="menuItem">
							<a class="dropdown-item" href="#" onClick="searchItem()" disabled="true"><img src="images/65.png"/> Search</a>
							<a class="dropdown-item" href="#" onClick="createItem()" disabled="true"><img src="images/78.png"/> Create</a>
							<a class="dropdown-item disabled" href="#" id="menuDeleteItem" onClick="deleteItem()" disabled="true"><img src="images/33.png"/> Delete</a>
						</div>
					</li>
					<li class="nav-item dropdown"><a class="nav-link dropdown-toggle"
						href="http://example.com" id="menuView" data-toggle="dropdown"
						aria-haspopup="true" aria-expanded="false">Views</a>
						<div class="dropdown-menu" id="menuViewDropDown" aria-labelledby="menuView">
						</div>
					</li>
					<li class="nav-item disabled"><a class="nav-link" href="#" id="menuCurrentItem">No item selected</a></li>
				</ul>
				<form class="form-inline mt-2 mt-md-0">
				<div>
					<input class="typeahead form-control mr-sm-2" type="text" id="menuSearchInput" placeholder="Search" aria-label="Search">
						</div>
					<!--button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button-->
				</form>
			</div>
		</nav>
	</header>
	<!-- Begin page content -->
	<main id="main" style="position:absolute;top:60px;width:100%; bottom:0px;">
	</main>
	<div id="alert" class="alert alert-warning" style="position:absolute;display:none;bottom:10px;left:10px;right:10px;vertical-align:center" role="alert">
  		<span id="alertBadge" class="badge" style="padding:8px;margin-right:15px">
  			<img id="alertIcon"/>
  			<strong id="alertLevel"/>
  		</span>
  		<span id="alertMessage"></span>
	</div>
	<svg id="strategic" 	style="width: 100%; height: 100%; display: none"></svg>
	<svg id="business" 		style="width: 100%; height: 100%; display: none"></svg>
	<svg id="logical" 		style="width: 100%; height: 100%; display: none"></svg>
	<svg id="service" 		style="width: 100%; height: 100%; display: none"></svg>
	<svg id="process" 		style="width: 100%; height: 100%; display: none"></svg>
	<svg id="technical" 	style="width: 100%; height: 100%; display: none"></svg>
	<div id="viewItem" 		style="width: 100%; height: 100%; display: none"></div>
	<svg id="mapEurope"		style="width: 100%; height: 100%; display: none"></svg>
	<svg id="mapWorld"		style="width: 100%; height: 100%; display: none"></svg>
	<div id="searchItem" 	style="display: none"></div>
	<div id="createItem" 	style="display: none"></div>
	<div id="popup" 		style="display: none"></div>
	
	<!-- ========================================================== -->
	<!-- Placed at the end of the document so the pages load faster -->
	<script type="text/javascript" src="vendor/jquery/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="vendor/jquery/ui/1.12.1/jquery-ui.js"></script>
	<script type="text/javascript" src="vendor/svgtool/svg-pan-zoom.js"></script>
	<script type="text/javascript" src="vendor/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="vendor/jquery/typeahead/typeahead.jquery.min.js"></script>
	<script type="text/javascript" src="vendor/jquery/typeahead/handlebars.js"></script>
	<script type="text/javascript" src="vendor/jquery-panel/js/jquery-panel.js"></script>
	<script type="text/javascript" src="vendor/graf/util.js"></script>
	<script type="text/javascript">
		// Auto completion lors de la recherche
		$('input.typeahead').typeahead({
				minLength: 3,
			  	highlight: true
			},{
			source : function (query, syncResults, asyncResults){
				return $.get('api/element.php', { name: query }, function (data) {
		        	var elements = data.elements;
		        	var result = new Array();
		        	for(var i = 0; i < elements.length; i++){
		        		elements[i].categoryName = elements[i].category.name;
		        		result.push(elements[i]);
		        	}
		        	asyncResults(result);
		        });
			},
			display : Handlebars.compile(''),
			templates: {
				//header : "Items found",
			    /*empty: [
			      '<div class="empty-message">',
			        'unable to find any item that match the current query',
			      '</div>'
			    ].join('\n'),*/
			    suggestion: Handlebars.compile('<div><strong>{{categoryName}}</strong> – {{name}}</div>')
			}
		}).bind('typeahead:select', function(ev, suggestion) {
			  openItem(suggestion);
		});
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
    	var viewDescription = new Array();
    	viewDescription['strategic'] 	= { url : 'views/view_strategique.php', class : 'panel-purple', noItemSupported : true, static : false,	svg : true, 	title : "Strategical view"};
    	viewDescription['business'] 	= { url : 'views/view_business.php', 	class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Business view"};
    	viewDescription['logical'] 		= { url : 'views/view_logique.php', 	class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Logical view"};
    	viewDescription['service'] 		= { url : 'views/view_service.php', 	class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Service view"};
    	viewDescription['process'] 		= { url : 'views/view_process.php', 	class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Process view"};
    	viewDescription['technical']	= { url : 'views/view_technical.php', 	class : 'panel-purple', noItemSupported : false, static : false, svg : true, 	title : "Technical view"};
    	viewDescription['viewItem']		= { url : 'forms/viewItem.html', 		class : 'panel-purple', noItemSupported : false, static : true,  svg : false, 	title : "Detail"};
    	viewDescription['mapEurope']	= { url : 'views/view_map.php?map=europe', 		class : 'panel-purple', noItemSupported : true, static : false,  svg : true, 	title : "Map - Europe"};
    	viewDescription['mapWorld']		= { url : 'views/view_map.php?map=world', 		class : 'panel-purple', noItemSupported : true, static : false,  svg : true, 	title : "Map - World"};
    	var views = new Array();
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
	                			//console.log(exception);
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
    	var itemsList = new Array();
    	function sendMessage(level,message){
        	var wait = 1000;
        	if (level == "warning"){
        		$("#alert").attr('class', "alert alert-warning");
        		$("#alertBadge").attr('class', "badge badge-warning");
        		$("#alertLevel").text("Warning");
        		$("#alertIcon").attr("src","images/58.png");
        		wait = 2800;
        	} else if (level == "info"){
        		$("#alert").attr('class', "alert alert-info");
        		$("#alertBadge").attr('class', "badge badge-info");
        		$("#alertLevel").text("Info");
        		$("#alertIcon").attr("src","images/4.png");
        	} else if (level == "success"){
        		$("#alert").attr('class', "alert alert-success");
        		$("#alertBadge").attr('class', "badge badge-success");
        		$("#alertLevel").text("Success");
        		$("#alertIcon").attr("src","images/3.png");
        	} else if ((level == "error") || (level == "danger")){
        		$("#alert").attr('class', "alert alert-danger");
        		$("#alertBadge").attr('class', "badge badge-danger");
        		$("#alertIcon").attr("src","images/89.png");
        		$("#alertLevel").text("Error");
        		wait = 3000;
        	} else if (level == "primary"){
        		$("#alert").attr('class', "alert alert-primary");
        		$("#alertBadge").attr('class', "badge badge-primary");
        		$("#alertLevel").text("Info");
        		$("#alertIcon").attr("src","images/49.png");
        	} else {
        		$("#alert").attr('class', "alert alert-primary");
        		$("#alertBadge").attr('class', "badge badge-primary");
        		$("#alertLevel").text("");
        		$("#alertIcon").attr("src","images/49.png");
        	}
    		$("#alertMessage").text(message);
	   		$("#alert").show("slide").delay(wait).hide("fade", {}, 1200);
    	}
    	function home(){
    		global.currentItem = null;
    		itemsList = new Array();
    		applyItem(null);
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
    	function addItem(itemId){
        	if (global.currentItem == null){
            	sendMessage("warning","No current item selected");
            	return;
        	}
        	if (itemId == global.currentItem.id){
        		sendMessage("warning","Cannot add an item to itself");
            	return;
        	}
        	sendMessage("warning","Not yet implemented");
    	}
    	function createItem(){
    		$("#createItem").panel({
            	url 	: 'forms/createItem.html?id='+Math.random(),
            	class 	: 'panel-green',
            	title 	: "Create an item",
            	buttons : ["reload","close"]
            }).state("maximized");
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
    	function applyItem(item){
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
    	function showContextMenu(what,id){
	    	$.getJSON( "api/element.php?id="+id, function(result) {
				var element = result.elements[0];
				var html = "<p>Instance</p>";
				html += "<b>Nom</b> : "+element.name+"<br/><br/>";
				html += "<hr/>";
				html+="<button onclick='hidePopup();svgElementClicked(\"\",\""+id+"\",0)'><img src='images/63.png'/> ouvrir</button>";
				html += " <button onclick='hidePopup();deleteItem(\""+id+"\")'><img src='images/14.png'/> supprimer</button>";
				html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
				showPopup("Détail",html);
			}).fail(function(jxqr,textStatus,error) {
				sendMessage("error","Unable to get item information : "+error);
			});
    	}
    	function searchItem(){
        	$("#searchItem").panel({
            	url : 'forms/searchItem.html',
            	class : 'panel-green',
            	title : "Search an item",
            	//buttons : ["reload","close"]
            });//.state("maximized");
    	}
    	function openItem(item){
    		if (global.currentItem != null){
    			itemsList.push(global.currentItem);
        	}
    		global.currentItem = item;
    		applyItem(item);
    	}
    	function svgElementClicked(what,id,button){
        	if (button == 0){ // Left button
        		$.getJSON( "api/element.php?id="+id, function(result) {
    				var element = result.elements[0];
        			openItem(element);//{id : id, category : what});
        		});
        	} else { // Right button
        		showContextMenu(what,id);
        	}
    	}
    	var global = {
    		itemCategories 	: null,
    		views 			: null,
    		currentItem 	: null
    	};
    	$(function() {
    		var menuViewDropDown = $("#menuViewDropDown");
    		Object.keys(viewDescription).forEach(function (index) {
        		menuViewDropDown.append($("<a class='dropdown-item' href='#' onClick='addView(\""+index+"\")'>"+viewDescription[index].title+"</a>"));
        	});
        	$("#main").panelFrame();
        	$.getJSON( "api/element_class.php", function(result) {
        		global.itemCategories = result.categories;
        	}).fail(function(jxqr,textStatus,error) {
        		sendMessage("error","Unable to load item classes : "+error);
        	});
        	$.getJSON( "api/view.php?areas=list", function(result) {
        		global.views = result.views;
        	}).fail(function(jxqr,textStatus,error) {
        		sendMessage("error","Unable to load views : "+error);
        	});
    	});
    </script>
</body>
</html>