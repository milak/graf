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
<body oncontextmenu="event.preventDefault()" onresize="applyItem(currentItem)">
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
					<li class="nav-item active"><a class="nav-link" href="#" onClick="back()">Back <span
							class="sr-only">(current)</span></a></li>
					<li class="nav-item"><a class="nav-link" href="#" onClick="searchItem()">Search
							an item</a></li>
					<li class="nav-item dropdown"><a class="nav-link dropdown-toggle"
						href="http://example.com" id="menuCurrentItem" data-toggle="dropdown"
						aria-haspopup="true" aria-expanded="false">Choose an item</a>
						<div class="dropdown-menu" aria-labelledby="menuCurrentItem">
							<a class="dropdown-item disabled" href="#" id="menuDeleteItem" onClick="deleteItem()" disabled="true">Delete</a>
						</div></li>
					<li class="nav-item dropdown"><a class="nav-link dropdown-toggle"
						href="http://example.com" id="menuView" data-toggle="dropdown"
						aria-haspopup="true" aria-expanded="false">Views</a>
						<div class="dropdown-menu" id="menuViewDropDown" aria-labelledby="menuView">
						</div>
					</li>
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
	<main id="main" style="position:absolute;top:60px;width:100%; bottom:0px;"> </main>
	<svg id="strategic" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="business" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="logical" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="service" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="process" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="technical" style="width: 100%; height: 100%; display: none"></svg>
	<div id="viewItem" style="width: 100%; height: 100%; display: none"></div>
	<div id="popup" style="display: none"></div>
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
		$('input.typeahead').typeahead({
				minLength: 3,
			  	highlight: true
			},{
			source : function (query, syncResults, asyncResults){
				return $.get('api/element.php', { name: query }, function (data) {
		        	var elements = data.elements;
		        	var result = new Array();
		        	for(var i = 0; i < elements.length; i++){
		        		result.push({category : elements[i].category.name, id : elements[i].id, name : elements[i].name});
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
			    suggestion: Handlebars.compile('<div><strong>{{category}}</strong> – {{name}}</div>')
			}
		}).bind('typeahead:select', function(ev, suggestion) {
			  openItem(suggestion);
		});
		function menuSearchTyping(){
        	var menuSearchInput = $("#menuSearchInput").val();
        	if (menuSearchInput.length > 3){
            	// TODO chercher pour de vrai sur le nom et non sur l'id
        		$.getJSON( "api/element.php?name="+menuSearchInput, function(result) {
            		if (result.elements.length > 0){
            			var elements = "";
            			var html = "";
            			for (var i = 0; i < result.elements; i++){
                			var element = result.elements[i];
            				html += "<a class='dropdown-item' href='#' onClick='openItem({id:\""+element.id+"\"})'>"+element.name+"</a>";
            			}
            			
            		}
        			
        		});
        	}
    	}
    	var viewDescription = new Array();
    	viewDescription['strategic'] 	= { url : 'views/view_strategique.php', class : 'panel-purple', static : true,	svg : true, 	title : "Strategical view"};
    	viewDescription['business'] 	= { url : 'views/view_business.php', 	class : 'panel-purple', static : false, svg : true, 	title : "Business view"};
    	viewDescription['logical'] 		= { url : 'views/view_logique.php', 	class : 'panel-purple', static : false, svg : true, 	title : "Logical view"};
    	viewDescription['service'] 		= { url : 'views/view_service.php', 	class : 'panel-purple', static : false, svg : true, 	title : "Service view"};
    	viewDescription['process'] 		= { url : 'views/view_process.php', 	class : 'panel-purple', static : false, svg : true, 	title : "Process view"};
    	viewDescription['technical']	= { url : 'views/view_technique.php', 	class : 'panel-purple', static : false, svg : true, 	title : "Technical view"};
    	viewDescription['viewItem']		= { url : 'forms/viewItem.html', 		class : 'panel-purple', static : false, svg : false, 	title : "Detail"};
    	
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
               		if (currentItem != null){
                	   	panel.url = url + "?id="+currentItem.id;
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
    	var currentItem = null;
    	function home(){
    		currentItem = null;
    		itemsList = new Array();
    		applyItem(null);
    	}
    	function back(){
        	if (itemsList.length > 0){
        		currentItem = itemsList.pop();
        		applyItem(currentItem);
        	} else {
        		currentItem = null;
        		applyItem(null);
        	}
    	}
    	function applyItem(item){
        	if (item != null){
        		$.getJSON( "api/element.php?id="+item.id, function(result) {
        			var element = result.elements[0];
        			currentItem = element;
        			$("#menuCurrentItem").html(element.category.name+" - "+element.name);
        			//$("#menuDeleteItem").prop( "disabled", false);
        			$("#menuDeleteItem").attr('class', "dropdown-item");

        			for (var i = 0; i < views.length; i++){
                    	var view = views[i];
                    	//if (!view.viewDescription.static){
                   			view.reload(view.viewDescription.url+"?id="+item.id);
                    	//}
                	}
        			
        		});
        	} else {
        		$("#menuCurrentItem").html("Choose an item");
        		//$("#menuDeleteItem").prop( "disabled", true );
        		$("#menuDeleteItem").attr('class', "dropdown-item disabled");
        		for (var i = 0; i < views.length; i++){
                	var view = views[i];
                	if (!view.viewDescription.static){
                   		view.reload(null);
                	}
            	}
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
				showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
			});
    	}
    	function searchItem(){
        	$("#popup").panel({
            	url : 'forms/searchItem.html?14',
            	class : 'panel-green',
            	title : "Search an item",
            	buttons : ["close"]
            }).state("maximized");
    	}
    	function openItem(item){
    		if (currentItem != null){
    			itemsList.push(currentItem);
        	}
    		currentItem = item;
    		applyItem(item);
    	}
    	function svgElementClicked(what,id,button){
        	if (button == 0){ // Left button
        		openItem({id : id, category : what});
        	} else { // Right button
        		showContextMenu(what,id);
        	}
    	}
    	$(function() {
    		var menuViewDropDown = $("#menuViewDropDown");
    		Object.keys(viewDescription).forEach(function (index) {
        		menuViewDropDown.append($("<a class='dropdown-item' href='#' onClick='addView(\""+index+"\")'>"+viewDescription[index].title+"</a>"));
        	});
        	$("#main").panelFrame();
    	});
    </script>
</body>
</html>